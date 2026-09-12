<?php

declare(strict_types=1);

namespace Rehla\Wallet\Queries;

use Rehla\Wallet\Data\WalletData;
use Rehla\Wallet\Models\Wallet;

final class ListAllWallets
{
    /**
     * @return list<WalletData>
     */
    public function execute(): array
    {
        return Wallet::orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Wallet $w): WalletData => $w->toData())
            ->all();
    }
}
