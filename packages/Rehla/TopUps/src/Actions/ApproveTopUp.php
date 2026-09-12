<?php

declare(strict_types=1);

namespace Rehla\TopUps\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Identity\Contracts\AuthorizesActor;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Notifications\Data\OutboxMessageData;
use Rehla\TopUps\Data\ApproveTopUpData;
use Rehla\TopUps\Data\TopUpData;
use Rehla\TopUps\Enums\TopUpStatus;
use Rehla\TopUps\Exceptions\InvalidTopUpTransitionException;
use Rehla\TopUps\Exceptions\TopUpAccessDeniedException;
use Rehla\TopUps\Models\TopUpRequest;
use Rehla\Wallet\Contracts\WalletCreditor;
use Rehla\Wallet\Data\CreditWalletData;

final class ApproveTopUp
{
    public function __construct(
        private readonly WalletCreditor $walletCreditor,
        private readonly ?AuditWriter $auditWriter = null,
        private readonly ?OutboxWriter $outboxWriter = null,
        private readonly ?AuthorizesActor $authorizer = null,
    ) {}

    public function execute(ApproveTopUpData $data): TopUpData
    {
        return DB::transaction(function () use ($data): TopUpData {
            /** @var TopUpRequest $topUp */
            $topUp = TopUpRequest::query()->where('id', $data->topUpId)->lockForUpdate()->firstOrFail();

            if ($this->authorizer !== null && ! $this->authorizer->allows($data->actor, AbilityName::TopUpsReview)) {
                throw TopUpAccessDeniedException::reviewDenied();
            }

            if ($topUp->status === TopUpStatus::Approved) {
                return $topUp->toData();
            }

            if ($topUp->status !== TopUpStatus::UnderReview) {
                throw InvalidTopUpTransitionException::cannotTransition($topUp->status->value, TopUpStatus::Approved->value);
            }

            $credit = $this->walletCreditor->credit(new CreditWalletData(
                walletId: (string) $topUp->wallet_id,
                amountMinor: (int) $topUp->amount_minor,
                referenceType: 'topup',
                referenceId: (string) $topUp->id,
                idempotencyKey: 'topup:'.$topUp->id,
                metadata: [
                    'reviewed_by' => $data->actor->id,
                    'transaction_reference' => (string) $topUp->transaction_reference,
                ],
            ));

            $now = CarbonImmutable::now();

            $topUp->update([
                'status' => TopUpStatus::Approved,
                'reviewed_by' => $data->actor->id,
                'decided_at' => $now,
                'credit_ledger_entry_id' => $credit->entryId,
            ]);

            if ($this->auditWriter !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $data->actor->type,
                    actorId: $data->actor->id,
                    action: 'topup.approved',
                    subjectType: 'topup_request',
                    subjectId: (string) $topUp->id,
                    metadata: [
                        'amount_minor' => (int) $topUp->amount_minor,
                        'wallet_id' => (string) $topUp->wallet_id,
                        'credit_ledger_entry_id' => $credit->entryId,
                    ],
                ));
            }

            if ($this->outboxWriter !== null) {
                $this->outboxWriter->append(new OutboxMessageData(
                    eventName: 'top_up.approved',
                    aggregateType: 'topup_request',
                    aggregateId: (string) $topUp->id,
                    payload: [
                        'top_up_id' => (string) $topUp->id,
                        'account_id' => (string) $topUp->account_id,
                        'wallet_id' => (string) $topUp->wallet_id,
                        'amount_minor' => (int) $topUp->amount_minor,
                        'credit_ledger_entry_id' => $credit->entryId,
                        'reviewed_by' => $data->actor->id,
                        'decided_at' => $now->toIso8601String(),
                    ],
                    deduplicationKey: 'topup:approved:'.$topUp->id,
                ));
            }

            return $topUp->fresh()->toData();
        });
    }
}
