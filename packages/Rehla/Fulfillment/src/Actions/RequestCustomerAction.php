<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Fulfillment\Data\CustomerActionRequestData;
use Rehla\Fulfillment\Data\RequestCustomerActionData;
use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Fulfillment\Exceptions\ExecutionAccessDeniedException;
use Rehla\Fulfillment\Exceptions\InvalidExecutionTransitionException;
use Rehla\Fulfillment\Models\CustomerActionRequest;
use Rehla\Fulfillment\Models\ExecutionStatusHistory;
use Rehla\Fulfillment\Models\ServiceExecution;
use Rehla\Fulfillment\Support\ExecutionStateMachine;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Notifications\Data\OutboxMessageData;

final class RequestCustomerAction
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
        private readonly ?OutboxWriter $outboxWriter = null,
    ) {}

    public function execute(RequestCustomerActionData $data): CustomerActionRequestData
    {
        if ($data->actor->type === 'staff' && ! in_array(AbilityName::ExecutionsManage->value, $data->actor->abilities, true)) {
            throw ExecutionAccessDeniedException::manageDenied();
        }

        return DB::transaction(function () use ($data): CustomerActionRequestData {
            /** @var ServiceExecution $execution */
            $execution = ServiceExecution::query()->where('id', $data->executionId)->lockForUpdate()->firstOrFail();

            if (! ExecutionStateMachine::allows($execution->status, ExecutionStatus::CustomerActionRequired)) {
                throw InvalidExecutionTransitionException::cannotTransition($execution->status->value, ExecutionStatus::CustomerActionRequired->value);
            }

            $now = CarbonImmutable::now();
            $currentStatus = $execution->status;

            $execution->update([
                'status' => ExecutionStatus::CustomerActionRequired,
                'last_status_at' => $now,
                'updated_at' => $now,
            ]);

            ExecutionStatusHistory::create([
                'id' => (string) Str::uuid(),
                'execution_id' => $execution->id,
                'from_status' => $currentStatus->value,
                'to_status' => ExecutionStatus::CustomerActionRequired->value,
                'actor_type' => $data->actor->type,
                'actor_id' => $data->actor->id,
                'reason' => 'Customer action requested: '.$data->descriptionEn,
                'created_at' => $now,
            ]);

            $actionRequest = CustomerActionRequest::create([
                'id' => (string) Str::uuid(),
                'execution_id' => $execution->id,
                'requested_by' => $data->actor->id,
                'description_en' => $data->descriptionEn,
                'description_ar' => $data->descriptionAr,
                'required_document_purpose' => $data->requiredDocumentPurpose,
                'status' => 'open',
                'due_at' => $data->dueAt,
                'created_at' => $now,
            ]);

            if ($this->auditWriter !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $data->actor->type,
                    actorId: $data->actor->id,
                    action: 'customer_action.requested',
                    subjectType: 'service_execution',
                    subjectId: (string) $execution->id,
                    metadata: [
                        'action_request_id' => (string) $actionRequest->id,
                        'required_document_purpose' => $data->requiredDocumentPurpose,
                    ],
                ));
            }

            if ($this->outboxWriter !== null) {
                $this->outboxWriter->append(new OutboxMessageData(
                    eventName: 'customer_action.requested',
                    aggregateType: 'service_execution',
                    aggregateId: (string) $execution->id,
                    payload: [
                        'execution_id' => (string) $execution->id,
                        'account_id' => (string) $execution->account_id,
                        'action_request_id' => (string) $actionRequest->id,
                        'description_en' => $data->descriptionEn,
                        'description_ar' => $data->descriptionAr,
                        'required_document_purpose' => $data->requiredDocumentPurpose,
                    ],
                    deduplicationKey: 'customer_action:requested:'.$actionRequest->id,
                ));
            }

            return new CustomerActionRequestData(
                id: (string) $actionRequest->id,
                executionId: (string) $actionRequest->execution_id,
                descriptionEn: (string) $actionRequest->description_en,
                descriptionAr: (string) $actionRequest->description_ar,
                requiredDocumentPurpose: $actionRequest->required_document_purpose ? (string) $actionRequest->required_document_purpose : null,
                status: (string) $actionRequest->status,
                dueAt: $actionRequest->due_at ? CarbonImmutable::parse($actionRequest->due_at) : null,
                createdAt: CarbonImmutable::parse($actionRequest->created_at),
            );
        });
    }
}
