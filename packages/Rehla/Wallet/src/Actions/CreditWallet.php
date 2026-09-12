<?php

declare(strict_types=1);

namespace Rehla\Wallet\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Wallet\Data\CreditResult;
use Rehla\Wallet\Data\CreditWalletData;
use Rehla\Wallet\Enums\LedgerEntryType;
use Rehla\Wallet\Exceptions\WalletNotFoundException;
use Rehla\Wallet\Models\LedgerEntry;
use Rehla\Wallet\Models\Wallet;

final class CreditWallet
{
    public function execute(CreditWalletData $data): CreditResult
    {
        return DB::transaction(function () use ($data): CreditResult {
            // Check for idempotent replay
            /** @var LedgerEntry|null $existing */
            $existing = LedgerEntry::where('wallet_id', $data->walletId)
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existing) {
                return new CreditResult(
                    walletId: (string) $existing->wallet_id,
                    entryId: (string) $existing->id,
                    amountMinor: (int) $existing->amount_minor,
                    balanceAfterMinor: (int) $existing->balance_after_minor,
                    isReplay: true,
                );
            }

            /** @var Wallet|null $wallet */
            $wallet = Wallet::lockForUpdate()->find($data->walletId);
            if (! $wallet) {
                throw WalletNotFoundException::forId($data->walletId);
            }

            $currentBalance = (int) $wallet->balance_minor;
            $newBalance = $currentBalance + $data->amountMinor;
            $entryId = (string) Str::uuid();

            LedgerEntry::create([
                'id' => $entryId,
                'wallet_id' => $data->walletId,
                'type' => LedgerEntryType::Credit,
                'amount_minor' => $data->amountMinor,
                'balance_after_minor' => $newBalance,
                'reference_type' => $data->referenceType,
                'reference_id' => $data->referenceId,
                'idempotency_key' => $data->idempotencyKey,
                'metadata' => $data->metadata,
                'created_at' => new \DateTimeImmutable,
            ]);

            $wallet->balance_minor = $newBalance;
            $wallet->lock_version = ((int) $wallet->lock_version) + 1;
            $wallet->save();

            return new CreditResult(
                walletId: $data->walletId,
                entryId: $entryId,
                amountMinor: $data->amountMinor,
                balanceAfterMinor: $newBalance,
                isReplay: false,
            );
        });
    }
}
