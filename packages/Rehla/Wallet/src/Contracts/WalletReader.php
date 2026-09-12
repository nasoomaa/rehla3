<?php

declare(strict_types=1);

namespace Rehla\Wallet\Contracts;

use Rehla\Wallet\Data\WalletBalance;
use Rehla\Wallet\Data\WalletData;
use Rehla\Wallet\Data\WalletEntryData;

interface WalletReader
{
    public function getBalance(string $walletId): WalletBalance;

    public function getBalanceByAccount(string $accountId): WalletBalance;

    public function getWalletByAccount(string $accountId): ?WalletData;

    /**
     * @return array<int, WalletEntryData>
     */
    public function listEntries(string $walletId, int $limit = 50): array;
}
