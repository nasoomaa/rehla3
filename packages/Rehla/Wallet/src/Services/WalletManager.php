<?php

declare(strict_types=1);

namespace Rehla\Wallet\Services;

use Rehla\Wallet\Actions\CreditWallet;
use Rehla\Wallet\Actions\DebitWallet;
use Rehla\Wallet\Contracts\WalletCreditor;
use Rehla\Wallet\Contracts\WalletDebitor;
use Rehla\Wallet\Contracts\WalletReader;
use Rehla\Wallet\Data\CreditResult;
use Rehla\Wallet\Data\CreditWalletData;
use Rehla\Wallet\Data\DebitResult;
use Rehla\Wallet\Data\DebitWalletData;
use Rehla\Wallet\Data\WalletBalance;
use Rehla\Wallet\Data\WalletData;
use Rehla\Wallet\Models\Wallet;
use Rehla\Wallet\Queries\GetWalletBalance;
use Rehla\Wallet\Queries\ListWalletEntries;

final class WalletManager implements WalletCreditor, WalletDebitor, WalletReader
{
    public function __construct(
        private readonly GetWalletBalance $getBalanceQuery,
        private readonly ListWalletEntries $listEntriesQuery,
        private readonly CreditWallet $creditAction,
        private readonly DebitWallet $debitAction,
    ) {}

    public function getBalance(string $walletId): WalletBalance
    {
        return $this->getBalanceQuery->execute($walletId);
    }

    public function getBalanceByAccount(string $accountId): WalletBalance
    {
        return $this->getBalanceQuery->executeByAccount($accountId);
    }

    public function getWalletByAccount(string $accountId): ?WalletData
    {
        /** @var Wallet|null $wallet */
        $wallet = Wallet::where('account_id', $accountId)->first();

        return $wallet?->toData();
    }

    public function listEntries(string $walletId, int $limit = 50): array
    {
        return $this->listEntriesQuery->execute($walletId, $limit);
    }

    public function credit(CreditWalletData $data): CreditResult
    {
        return $this->creditAction->execute($data);
    }

    public function debit(DebitWalletData $data): DebitResult
    {
        return $this->debitAction->execute($data);
    }
}
