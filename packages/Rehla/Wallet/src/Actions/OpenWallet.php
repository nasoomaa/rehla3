<?php

declare(strict_types=1);

namespace Rehla\Wallet\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Wallet\Data\WalletData;
use Rehla\Wallet\Models\Wallet;

final class OpenWallet
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(string $accountId, ?string $actorId = null, string $actorType = 'system'): WalletData
    {
        return DB::transaction(function () use ($accountId, $actorId, $actorType): WalletData {
            /** @var Wallet|null $existing */
            $existing = Wallet::where('account_id', $accountId)->first();
            if ($existing) {
                return $existing->toData();
            }

            $id = (string) Str::uuid();
            $wallet = Wallet::create([
                'id' => $id,
                'account_id' => $accountId,
                'currency' => 'SDG',
                'balance_minor' => 0,
                'lock_version' => 1,
            ]);

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'wallet.opened',
                    subjectType: 'wallet',
                    subjectId: $id,
                    metadata: ['account_id' => $accountId],
                ));
            }

            return $wallet->toData();
        });
    }
}
