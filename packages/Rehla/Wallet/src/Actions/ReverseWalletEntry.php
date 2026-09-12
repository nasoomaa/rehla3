<?php

declare(strict_types=1);

namespace Rehla\Wallet\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Wallet\Data\WalletEntryData;
use Rehla\Wallet\Enums\LedgerEntryType;
use Rehla\Wallet\Exceptions\InsufficientBalanceException;
use Rehla\Wallet\Exceptions\WalletNotFoundException;
use Rehla\Wallet\Models\LedgerEntry;
use Rehla\Wallet\Models\Wallet;

final class ReverseWalletEntry
{
    public function execute(
        string $walletId,
        string $entryIdToReverse,
        string $reason,
        string $idempotencyKey
    ): WalletEntryData {
        return DB::transaction(function () use ($walletId, $entryIdToReverse, $reason, $idempotencyKey): WalletEntryData {
            /** @var LedgerEntry|null $target */
            $target = LedgerEntry::find($entryIdToReverse);
            if (! $target || (string) $target->wallet_id !== $walletId) {
                throw new DomainException("Ledger entry to reverse not found: {$entryIdToReverse}");
            }

            /** @var Wallet|null $wallet */
            $wallet = Wallet::lockForUpdate()->find($walletId);
            if (! $wallet) {
                throw WalletNotFoundException::forId($walletId);
            }

            $currentBalance = (int) $wallet->balance_minor;
            $amount = (int) $target->amount_minor;

            // Determine delta
            if ($target->type === LedgerEntryType::Credit) {
                // Reversing a credit requires debiting the balance
                if ($currentBalance < $amount) {
                    throw InsufficientBalanceException::forWallet($walletId, $amount, $currentBalance);
                }
                $newBalance = $currentBalance - $amount;
            } else {
                // Reversing a debit credits the balance
                $newBalance = $currentBalance + $amount;
            }

            $reversalEntry = LedgerEntry::create([
                'id' => (string) Str::uuid(),
                'wallet_id' => $walletId,
                'type' => LedgerEntryType::Reversal,
                'amount_minor' => $amount,
                'balance_after_minor' => $newBalance,
                'reference_type' => 'ledger_reversal',
                'reference_id' => $entryIdToReverse,
                'idempotency_key' => $idempotencyKey,
                'reverses_entry_id' => $entryIdToReverse,
                'metadata' => ['reason' => $reason],
                'created_at' => new \DateTimeImmutable,
            ]);

            $wallet->balance_minor = $newBalance;
            $wallet->lock_version = ((int) $wallet->lock_version) + 1;
            $wallet->save();

            return $reversalEntry->toData();
        });
    }
}
