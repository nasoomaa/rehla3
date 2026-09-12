<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Documents\Actions\AttachDocument;
use Rehla\Documents\Contracts\OwnedDocuments;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\Fulfillment\Data\CustomerActionResponseData;
use Rehla\Fulfillment\Data\RespondToCustomerActionData;
use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Fulfillment\Exceptions\CustomerActionNotFoundException;
use Rehla\Fulfillment\Models\CustomerActionRequest;
use Rehla\Fulfillment\Models\CustomerActionResponse;
use Rehla\Fulfillment\Models\ExecutionDocument;
use Rehla\Fulfillment\Models\ExecutionStatusHistory;
use Rehla\Fulfillment\Models\ServiceExecution;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Notifications\Data\OutboxMessageData;

final class RespondToCustomerAction
{
    public function __construct(
        private readonly ?OwnedDocuments $ownedDocuments = null,
        private readonly ?AttachDocument $attachDocument = null,
        private readonly ?AuditWriter $auditWriter = null,
        private readonly ?OutboxWriter $outboxWriter = null,
    ) {}

    public function execute(RespondToCustomerActionData $data): CustomerActionResponseData
    {
        return DB::transaction(function () use ($data): CustomerActionResponseData {
            /** @var CustomerActionRequest|null $actionRequest */
            $actionRequest = CustomerActionRequest::query()->where('id', $data->actionRequestId)->lockForUpdate()->first();

            if ($actionRequest === null) {
                throw CustomerActionNotFoundException::forId($data->actionRequestId);
            }

            /** @var ServiceExecution $execution */
            $execution = ServiceExecution::query()->where('id', $actionRequest->execution_id)->lockForUpdate()->firstOrFail();

            if ($execution->account_id !== $data->accountId) {
                throw CustomerActionNotFoundException::forId($data->actionRequestId);
            }

            if ($actionRequest->status === 'resolved') {
                $existing = CustomerActionResponse::query()->where('action_request_id', $actionRequest->id)->first();
                if ($existing !== null) {
                    return $existing->toData();
                }
            }

            if ($actionRequest->status !== 'open') {
                throw new InvalidArgumentException('Action request is not open.');
            }

            if ($actionRequest->required_document_purpose !== null) {
                if ($data->documentId === null) {
                    throw new InvalidArgumentException('Document is required for this action request.');
                }
                if ($this->ownedDocuments !== null) {
                    $purpose = DocumentPurpose::tryFrom((string) $actionRequest->required_document_purpose) ?? DocumentPurpose::Other;
                    $this->ownedDocuments->assertCleanOwned([$data->documentId], $data->accountId, $purpose);
                }
                if ($this->attachDocument !== null) {
                    $this->attachDocument->handle($data->documentId, $data->accountId);
                }
            } elseif ($data->message === null || trim($data->message) === '') {
                throw new InvalidArgumentException('Message is required for this action request.');
            }

            $now = CarbonImmutable::now();

            if ($data->documentId !== null) {
                ExecutionDocument::create([
                    'id' => (string) Str::uuid(),
                    'execution_id' => $execution->id,
                    'document_id' => $data->documentId,
                    'purpose' => $actionRequest->required_document_purpose ?? 'customer_action_document',
                    'attached_at' => $now,
                ]);
            }

            $response = CustomerActionResponse::create([
                'id' => (string) Str::uuid(),
                'action_request_id' => $actionRequest->id,
                'account_id' => $data->accountId,
                'message' => $data->message ? trim($data->message) : null,
                'document_id' => $data->documentId,
                'submitted_at' => $now,
            ]);

            $actionRequest->update([
                'status' => 'resolved',
                'resolved_at' => $now,
            ]);

            $fromStatus = $execution->status;
            $execution->update([
                'status' => ExecutionStatus::RequestedActionReceived,
                'last_status_at' => $now,
                'updated_at' => $now,
            ]);

            ExecutionStatusHistory::create([
                'id' => (string) Str::uuid(),
                'execution_id' => $execution->id,
                'from_status' => $fromStatus->value,
                'to_status' => ExecutionStatus::RequestedActionReceived->value,
                'actor_type' => 'customer',
                'actor_id' => $data->accountId,
                'reason' => 'Customer submitted requested action',
                'created_at' => $now,
            ]);

            if ($this->auditWriter !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: 'customer',
                    actorId: $data->accountId,
                    action: 'customer_action.responded',
                    subjectType: 'service_execution',
                    subjectId: (string) $execution->id,
                    metadata: [
                        'action_request_id' => (string) $actionRequest->id,
                        'response_id' => (string) $response->id,
                    ],
                ));
            }

            if ($this->outboxWriter !== null) {
                $this->outboxWriter->append(new OutboxMessageData(
                    eventName: 'customer_action.responded',
                    aggregateType: 'service_execution',
                    aggregateId: (string) $execution->id,
                    payload: [
                        'execution_id' => (string) $execution->id,
                        'action_request_id' => (string) $actionRequest->id,
                        'response_id' => (string) $response->id,
                    ],
                    deduplicationKey: 'customer_action:responded:'.$actionRequest->id,
                ));
            }

            return $response->toData();
        });
    }
}
