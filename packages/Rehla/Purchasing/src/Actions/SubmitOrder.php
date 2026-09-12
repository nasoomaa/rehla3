<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Catalog\Contracts\ServiceCatalog;
use Rehla\Documents\Actions\AttachDocument;
use Rehla\Documents\Contracts\OwnedDocuments;
use Rehla\Forms\Contracts\FormSubmissionValidator;
use Rehla\Forms\Queries\GetPublishedForm;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Notifications\Data\OutboxMessageData;
use Rehla\Orders\Contracts\OrderWriter;
use Rehla\Orders\Data\CreatePaidOrderData;
use Rehla\Orders\Data\FormSnapshotData;
use Rehla\Orders\Data\ServiceSnapshotData;
use Rehla\Orders\Data\TravelerSnapshotData;
use Rehla\Purchasing\Contracts\ExecutionCreator;
use Rehla\Purchasing\Data\CreateExecutionData;
use Rehla\Purchasing\Data\SubmitOrderData;
use Rehla\Purchasing\Data\SubmitOrderResult;
use Rehla\Purchasing\Enums\PurchaseAttemptStatus;
use Rehla\Purchasing\Exceptions\FormVersionChangedException;
use Rehla\Purchasing\Exceptions\IdempotencyKeyReusedException;
use Rehla\Purchasing\Exceptions\PriceChangedException;
use Rehla\Purchasing\Exceptions\ServiceNotAvailableException;
use Rehla\Purchasing\Models\PurchaseAttempt;
use Rehla\Purchasing\Support\CanonicalPurchaseFingerprint;
use Rehla\Travelers\Queries\GetOwnedTravelerSnapshot;
use Rehla\Wallet\Contracts\WalletDebitor;
use Rehla\Wallet\Contracts\WalletReader;
use Rehla\Wallet\Data\DebitWalletData;
use Rehla\Wallet\Exceptions\WalletNotFoundException;

final class SubmitOrder
{
    public function __construct(
        private readonly ServiceCatalog $catalog,
        private readonly GetPublishedForm $getPublishedForm,
        private readonly FormSubmissionValidator $formSubmissionValidator,
        private readonly GetOwnedTravelerSnapshot $getOwnedTravelerSnapshot,
        private readonly WalletReader $walletReader,
        private readonly WalletDebitor $walletDebitor,
        private readonly OrderWriter $orderWriter,
        private readonly ExecutionCreator $executionCreator,
        private readonly ?OwnedDocuments $ownedDocuments = null,
        private readonly ?AttachDocument $attachDocument = null,
        private readonly ?AuditWriter $auditWriter = null,
        private readonly ?OutboxWriter $outboxWriter = null,
    ) {}

    public function execute(SubmitOrderData $data): SubmitOrderResult
    {
        return $this->handle($data);
    }

    public function handle(SubmitOrderData $data): SubmitOrderResult
    {
        $fingerprint = CanonicalPurchaseFingerprint::from([
            'account_id' => $data->accountId,
            'service_id' => $data->serviceId,
            'traveler_id' => $data->travelerId,
            'accepted_price_minor' => $data->acceptedPriceMinor,
            'accepted_price_version' => $data->acceptedPriceVersion,
            'form_version_id' => $data->formVersionId,
            'answers' => $data->answers,
            'document_ids' => $data->documentIds,
        ]);

        return DB::transaction(function () use ($data, $fingerprint): SubmitOrderResult {
            // 1. Insert or lock attempt
            DB::table('purchase_attempts')->insertOrIgnore([
                'id' => (string) Str::uuid(),
                'account_id' => $data->accountId,
                'idempotency_key' => $data->idempotencyKey,
                'request_fingerprint' => $fingerprint,
                'status' => PurchaseAttemptStatus::InProgress->value,
                'created_at' => CarbonImmutable::now(),
            ]);

            /** @var PurchaseAttempt $attempt */
            $attempt = PurchaseAttempt::where('account_id', $data->accountId)
                ->where('idempotency_key', $data->idempotencyKey)
                ->lockForUpdate()
                ->firstOrFail();

            if (! hash_equals($attempt->request_fingerprint, $fingerprint)) {
                throw IdempotencyKeyReusedException::forKey($data->idempotencyKey);
            }

            if ($attempt->isCompleted()) {
                $body = $attempt->response_body ?? [];

                return new SubmitOrderResult(
                    orderId: (string) ($body['order_id'] ?? $attempt->order_id ?? ''),
                    executionId: (string) ($body['execution_id'] ?? ''),
                    status: (string) ($body['status'] ?? ''),
                    amountPaidMinor: (int) ($body['amount_paid_minor'] ?? 0),
                    currency: (string) ($body['currency'] ?? 'SDG'),
                    metadata: array_merge((array) ($body['metadata'] ?? []), ['is_replay' => true]),
                );
            }

            // 2. Validate Service and Price
            $quote = $this->catalog->currentQuote($data->serviceId);
            if (! $quote->available) {
                throw ServiceNotAvailableException::forService($data->serviceId);
            }

            if ($quote->priceMinor !== $data->acceptedPriceMinor || $quote->quoteVersion !== $data->acceptedPriceVersion) {
                throw PriceChangedException::forService(
                    $data->serviceId,
                    $data->acceptedPriceMinor,
                    $data->acceptedPriceVersion,
                    $quote->priceMinor,
                    $quote->quoteVersion
                );
            }

            $serviceSnapshot = $this->catalog->serviceSnapshot($data->serviceId);
            if ($serviceSnapshot === null) {
                throw ServiceNotAvailableException::forService($data->serviceId);
            }

            // 3. Validate Form Version & Schema Submission
            $publishedForm = $this->getPublishedForm->handle($data->serviceId);
            if ($publishedForm === null || $publishedForm->id !== $data->formVersionId) {
                throw FormVersionChangedException::forVersion(
                    $data->serviceId,
                    $data->formVersionId,
                    $publishedForm?->id
                );
            }

            $validatedSubmission = $this->formSubmissionValidator->validate(
                $publishedForm->id,
                $data->answers,
                $data->documentIds,
            );

            // 4. Validate Traveler Ownership
            $travelerSnapshot = $this->getOwnedTravelerSnapshot->handle($data->accountId, $data->travelerId);

            // 5. Validate Documents
            if ($this->ownedDocuments !== null && ! empty($validatedSubmission->documentIds)) {
                $this->ownedDocuments->assertCleanOwned(
                    $validatedSubmission->documentIds,
                    $data->accountId,
                );
            }

            // 6. Check Wallet
            $wallet = $this->walletReader->getWalletByAccount($data->accountId);
            if ($wallet === null) {
                throw new WalletNotFoundException("Wallet not found for account {$data->accountId}");
            }

            // 7. Generate order ID and debit wallet
            $orderId = (string) Str::uuid();

            $debitResult = $this->walletDebitor->debit(new DebitWalletData(
                walletId: $wallet->id,
                amountMinor: $quote->priceMinor,
                referenceType: 'order',
                referenceId: $orderId,
                idempotencyKey: 'order_debit_'.$data->idempotencyKey,
                metadata: [
                    'account_id' => $data->accountId,
                    'service_id' => $data->serviceId,
                    'traveler_id' => $data->travelerId,
                ],
            ));

            // 8. Attach documents
            if ($this->attachDocument !== null && ! empty($validatedSubmission->documentIds)) {
                foreach ($validatedSubmission->documentIds as $docId) {
                    $this->attachDocument->handle($docId, $data->accountId);
                }
            }

            // 9. Create Paid Order
            $serviceSnapshotData = new ServiceSnapshotData(
                nameEn: $serviceSnapshot->name['en'] ?? '',
                nameAr: $serviceSnapshot->name['ar'] ?? '',
                descriptions: $serviceSnapshot->descriptions,
                requirements: $serviceSnapshot->requirements,
                expectedDuration: $serviceSnapshot->expectedDuration,
                notes: array_values(array_filter([$serviceSnapshot->notes['en'] ?? null, $serviceSnapshot->notes['ar'] ?? null])),
            );

            $travelerSnapshotData = new TravelerSnapshotData(
                fullName: $travelerSnapshot->fullName,
                dateOfBirth: $travelerSnapshot->dateOfBirth,
                gender: $travelerSnapshot->gender->value,
                passportNumber: $travelerSnapshot->passportNumber,
                passportIssuedAt: $travelerSnapshot->passportIssuedAt,
                passportExpiresAt: $travelerSnapshot->passportExpiresAt,
            );

            $formSnapshotData = new FormSnapshotData(
                formVersionId: $publishedForm->id,
                formVersion: $publishedForm->version,
                formChecksum: $publishedForm->checksum ?? hash('sha256', json_encode($publishedForm->fields)),
                schema: ['fields' => array_map(fn ($f) => (array) $f, $publishedForm->fields)],
                answers: $validatedSubmission->answers,
            );

            $paidOrder = $this->orderWriter->createPaid(new CreatePaidOrderData(
                accountId: $data->accountId,
                serviceId: $data->serviceId,
                travelerId: $data->travelerId,
                priceMinor: $quote->priceMinor,
                amountPaidMinor: $quote->priceMinor,
                currency: $quote->currency,
                debitLedgerEntryId: $debitResult->entryId,
                serviceSnapshot: $serviceSnapshotData,
                travelerSnapshot: $travelerSnapshotData,
                formSnapshot: $formSnapshotData,
                id: $orderId,
            ));

            // 10. Create Execution
            $execution = $this->executionCreator->create(new CreateExecutionData(
                orderId: $orderId,
                accountId: $data->accountId,
                travelerId: $data->travelerId,
                serviceId: $data->serviceId,
                formVersionId: $publishedForm->id,
                submissionAnswers: $validatedSubmission->answers,
                documentIds: $validatedSubmission->documentIds,
            ));

            // 11. Append Audit
            if ($this->auditWriter !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: 'customer',
                    actorId: $data->accountId,
                    action: 'order.submitted',
                    subjectType: 'order',
                    subjectId: $orderId,
                    metadata: [
                        'execution_id' => $execution->id,
                        'service_id' => $data->serviceId,
                        'traveler_id' => $data->travelerId,
                        'price_minor' => $quote->priceMinor,
                    ],
                ));
            }

            // 12. Append Outbox
            if ($this->outboxWriter !== null) {
                $this->outboxWriter->append(new OutboxMessageData(
                    eventName: 'order.submitted',
                    aggregateType: 'order',
                    aggregateId: $orderId,
                    payload: [
                        'order_id' => $orderId,
                        'execution_id' => $execution->id,
                        'account_id' => $data->accountId,
                        'service_id' => $data->serviceId,
                        'traveler_id' => $data->travelerId,
                        'amount_paid_minor' => $quote->priceMinor,
                        'currency' => $quote->currency,
                    ],
                    deduplicationKey: 'order:submitted:'.$orderId,
                ));
            }

            // 13. Complete purchase attempt
            $result = new SubmitOrderResult(
                orderId: $orderId,
                executionId: $execution->id,
                status: $execution->status,
                amountPaidMinor: $paidOrder->amountPaidMinor,
                currency: $paidOrder->currency,
                metadata: [
                    'created_at' => $paidOrder->createdAt->format(DATE_ATOM),
                    'is_replay' => false,
                ],
            );

            $attempt->update([
                'status' => PurchaseAttemptStatus::Completed,
                'order_id' => $orderId,
                'response_status' => 201,
                'response_body' => [
                    'order_id' => $result->orderId,
                    'execution_id' => $result->executionId,
                    'status' => $result->status,
                    'amount_paid_minor' => $result->amountPaidMinor,
                    'currency' => $result->currency,
                    'metadata' => $result->metadata,
                ],
                'completed_at' => CarbonImmutable::now(),
            ]);

            return $result;
        }, attempts: 3);
    }
}
