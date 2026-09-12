<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Controllers\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Rehla\Wallet\Exceptions\WalletNotFoundException;
use Rehla\Wallet\Queries\GetWalletBalance;
use Rehla\Wallet\Queries\ListWalletEntries;

final class WalletController
{
    public function __invoke(GetWalletBalance $getBalance, ListWalletEntries $listEntries): View
    {
        $userId = (string) Auth::id();
        $balanceMinor = 0;
        $entries = [];

        try {
            $balance = $getBalance->executeByAccount($userId);
            $balanceMinor = $balance->balanceMinor;
            $entries = $listEntries->execute($balance->walletId);
        } catch (WalletNotFoundException) {
            $balanceMinor = 0;
            $entries = [];
        }

        return view('rehla-web::account.wallet', [
            'balanceMinor' => $balanceMinor,
            'entries' => $entries,
        ]);
    }
}
