<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Fulfillment\Data\TransitionExecutionData;
use Rehla\Fulfillment\Exceptions\ExecutionAccessDeniedException;
use Rehla\Fulfillment\Exceptions\InvalidExecutionTransitionException;
use Rehla\Fulfillment\Models\ExecutionStatusHistory;
use Rehla\Fulfillment\Models\ServiceExecution;
use Rehla\Fulfillment\Support\ExecutionStateMachine;
use Rehla\Identity\Contracts\AuthorizesActor;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Notifications\Data\OutboxMessageData;
use Rehla\Purchasing\Data\ExecutionData;

final class TransitionExecution
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
        private readonly ?OutboxWriter $outboxWriter = null,
        private readonly ?AuthorizesActor $authorizer = null,
    ) {}

    public function execute(TransitionExecutionData $data): ExecutionData
    {
        if ($data->actor->type === 'staff' && ! in_array(AbilityName::ExecutionsManage->value, $data->actor->abilities, true)) {
            throw ExecutionAccessDeniedException::manageDenied();
        }

        return DB::transaction(function () use ($data): ExecutionData {
            /** @var ServiceExecution $execution */
            $execution = ServiceExecution::query()->where('id', $data->executionId)->lockForUpdate()->firstOrFail();

            $currentStatus = $execution->status;

            if (! ExecutionStateMachine::allows($currentStatus, $data->toStatus)) {
                throw InvalidExecutionTransitionException::cannotTransition($currentStatus->value, $data->toStatus->value);
            }

            $now = CarbonImmutable::now();

            $execution->update([
                'status' => $data->toStatus,
                'last_status_at' => $now,
                'updated_at' => $now,
            ]);

            ExecutionStatusHistory::create([
                'id' => (string) Str::uuid(),
                'execution_id' => $execution->id,
                'from_status' => $currentStatus->value,
                'to_status' => $data->toStatus->value,
                'actor_type' => $data->actor->type,
                'actor_id' => $data->actor->id,
                'reason' => $data->reason,
                'created_at' => $now,
            ]);

            if ($this->auditWriter !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $data->actor->type,
                    actorId: $data->actor->id,
                    action: 'execution.status_changed',
                    subjectType: 'service_execution',
                    subjectId: (string) $execution->id,
                    metadata: [
                        'from_status' => $currentStatus->value,
                        'to_status' => $data->toStatus->value,
                        'reason' => $data->reason,
                    ],
                ));
            }

            if ($this->outboxWriter !== null) {
                $this->outboxWriter->append(new OutboxMessageData(
                    eventName: 'execution.status_changed',
                    aggregateType: 'service_execution',
                    aggregateId: (string) $execution->id,
                    payload: [
                        'execution_id' => (string) $execution->id,
                        'order_id' => (string) $execution->order_id,
                        'account_id' => (string) $execution->account_id,
                        'from_status' => $currentStatus->value,
                        'to_status' => $data->toStatus->value,
                    ],
                    deduplicationKey: 'execution:status:'.$execution->id.':'.$data->toStatus->value,
                ));
            }

            return $execution->fresh()->toExecutionData();
        });
    }
}
