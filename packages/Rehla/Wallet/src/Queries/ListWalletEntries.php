<?php

declare(strict_types=1);

namespace Rehla\Wallet\Queries;

use Rehla\Wallet\Data\WalletEntryData;
use Rehla\Wallet\Models\LedgerEntry;

final class ListWalletEntries
{
    /**
     * @return array<int, WalletEntryData>
     */
    public function execute(string $walletId, int $limit = 50): array
    {
        return LedgerEntry::where('wallet_id', $walletId)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn (LedgerEntry $e): WalletEntryData => $e->toData())
            ->all();
    }
}
