<?php

declare(strict_types=1);

namespace Rehla\TopUps\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Identity\Contracts\AuthorizesActor;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Notifications\Data\OutboxMessageData;
use Rehla\TopUps\Data\RejectTopUpData;
use Rehla\TopUps\Data\TopUpData;
use Rehla\TopUps\Enums\TopUpStatus;
use Rehla\TopUps\Exceptions\InvalidTopUpTransitionException;
use Rehla\TopUps\Exceptions\TopUpAccessDeniedException;
use Rehla\TopUps\Models\TopUpRequest;

final class RejectTopUp
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
        private readonly ?OutboxWriter $outboxWriter = null,
        private readonly ?AuthorizesActor $authorizer = null,
    ) {}

    public function execute(RejectTopUpData $data): TopUpData
    {
        if (trim($data->rejectionReason) === '') {
            throw new InvalidArgumentException('Rejection reason cannot be empty.');
        }

        return DB::transaction(function () use ($data): TopUpData {
            /** @var TopUpRequest $topUp */
            $topUp = TopUpRequest::query()->where('id', $data->topUpId)->lockForUpdate()->firstOrFail();

            if ($this->authorizer !== null && ! $this->authorizer->allows($data->actor, AbilityName::TopUpsReview)) {
                throw TopUpAccessDeniedException::reviewDenied();
            }

            if ($topUp->status === TopUpStatus::Rejected) {
                return $topUp->toData();
            }

            if ($topUp->status !== TopUpStatus::UnderReview) {
                throw InvalidTopUpTransitionException::cannotTransition($topUp->status->value, TopUpStatus::Rejected->value);
            }

            $now = CarbonImmutable::now();

            $topUp->update([
                'status' => TopUpStatus::Rejected,
                'reviewed_by' => $data->actor->id,
                'decided_at' => $now,
                'rejection_reason' => trim($data->rejectionReason),
            ]);

            if ($this->auditWriter !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $data->actor->type,
                    actorId: $data->actor->id,
                    action: 'topup.rejected',
                    subjectType: 'topup_request',
                    subjectId: (string) $topUp->id,
                    metadata: [
                        'reason' => trim($data->rejectionReason),
                    ],
                ));
            }

            if ($this->outboxWriter !== null) {
                $this->outboxWriter->append(new OutboxMessageData(
                    eventName: 'top_up.rejected',
                    aggregateType: 'topup_request',
                    aggregateId: (string) $topUp->id,
                    payload: [
                        'top_up_id' => (string) $topUp->id,
                        'account_id' => (string) $topUp->account_id,
                        'rejection_reason' => trim($data->rejectionReason),
                        'reviewed_by' => $data->actor->id,
                        'decided_at' => $now->toIso8601String(),
                    ],
                    deduplicationKey: 'topup:rejected:'.$topUp->id,
                ));
            }

            return $topUp->fresh()->toData();
        });
    }
}
