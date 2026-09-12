<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Rehla\Wallet\Queries\ListAllWallets;

final class WalletController extends Controller
{
    public function index(ListAllWallets $listAllWallets): View
    {
        $wallets = $listAllWallets->execute();

        return view('rehla-admin::wallets.index', [
            'wallets' => $wallets,
        ]);
    }
}
