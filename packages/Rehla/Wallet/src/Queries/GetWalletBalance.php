<?php

declare(strict_types=1);

namespace Rehla\Wallet\Queries;

use Rehla\Wallet\Data\WalletBalance;
use Rehla\Wallet\Exceptions\WalletNotFoundException;
use Rehla\Wallet\Models\Wallet;

final class GetWalletBalance
{
    public function execute(string $walletId): WalletBalance
    {
        /** @var Wallet|null $wallet */
        $wallet = Wallet::find($walletId);
        if (! $wallet) {
            throw WalletNotFoundException::forId($walletId);
        }

        return $wallet->toBalance();
    }

    public function executeByAccount(string $accountId): WalletBalance
    {
        /** @var Wallet|null $wallet */
        $wallet = Wallet::where('account_id', $accountId)->first();
        if (! $wallet) {
            throw WalletNotFoundException::forAccount($accountId);
        }

        return $wallet->toBalance();
    }
}
