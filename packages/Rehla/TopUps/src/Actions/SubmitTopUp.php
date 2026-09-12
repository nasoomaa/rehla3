<?php

declare(strict_types=1);

namespace Rehla\TopUps\Actions;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Documents\Actions\AttachDocument;
use Rehla\Documents\Contracts\OwnedDocuments;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\TopUps\Data\SubmitTopUpData;
use Rehla\TopUps\Data\TopUpData;
use Rehla\TopUps\Enums\TopUpStatus;
use Rehla\TopUps\Exceptions\DuplicateTransactionReferenceException;
use Rehla\TopUps\Exceptions\InactiveBankAccountException;
use Rehla\TopUps\Exceptions\InvalidTopUpAmountException;
use Rehla\TopUps\Models\CompanyBankAccount;
use Rehla\TopUps\Models\TopUpRequest;
use Rehla\TopUps\Support\NormalizeTransactionReference;

final class SubmitTopUp
{
    public const MINIMUM_AMOUNT_MINOR = 500_000; // 5,000 SDG

    public function __construct(
        private readonly ?OwnedDocuments $ownedDocuments = null,
        private readonly ?AttachDocument $attachDocument = null,
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(SubmitTopUpData $data): TopUpData
    {
        if ($data->amountMinor < self::MINIMUM_AMOUNT_MINOR) {
            throw InvalidTopUpAmountException::belowMinimum($data->amountMinor, self::MINIMUM_AMOUNT_MINOR);
        }

        return DB::transaction(function () use ($data): TopUpData {
            /** @var CompanyBankAccount|null $bank */
            $bank = CompanyBankAccount::find($data->bankAccountId);
            if (! $bank || ! $bank->active) {
                throw InactiveBankAccountException::forId($data->bankAccountId);
            }

            $normalizedRef = NormalizeTransactionReference::normalize($data->transactionReference);

            // Check uniqueness per bank account
            $alreadyExists = TopUpRequest::where('bank_account_id', $data->bankAccountId)
                ->where('normalized_reference', $normalizedRef)
                ->exists();

            if ($alreadyExists) {
                throw DuplicateTransactionReferenceException::forReference($data->transactionReference);
            }

            // Verify clean owned receipt
            if ($this->ownedDocuments !== null) {
                $this->ownedDocuments->assertCleanOwned(
                    [$data->receiptDocumentId],
                    $data->accountId,
                    DocumentPurpose::BankReceipt
                );
            }

            // Attach receipt document
            if ($this->attachDocument !== null) {
                $this->attachDocument->handle($data->receiptDocumentId, $data->accountId);
            }

            $id = (string) Str::uuid();
            $now = new DateTimeImmutable;

            $topUp = TopUpRequest::create([
                'id' => $id,
                'account_id' => $data->accountId,
                'wallet_id' => $data->walletId,
                'bank_account_id' => $data->bankAccountId,
                'amount_minor' => $data->amountMinor,
                'transaction_reference' => $data->transactionReference,
                'normalized_reference' => $normalizedRef,
                'receipt_document_id' => $data->receiptDocumentId,
                'status' => TopUpStatus::UnderReview,
                'submitted_at' => $now,
            ]);

            if ($this->auditWriter !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: 'customer',
                    actorId: $data->accountId,
                    action: 'topup.submitted',
                    subjectType: 'topup_request',
                    subjectId: $id,
                    metadata: [
                        'bank_account_id' => $data->bankAccountId,
                        'amount_minor' => $data->amountMinor,
                        'normalized_reference' => $normalizedRef,
                    ],
                ));
            }

            return $topUp->toData();
        });
    }
}
