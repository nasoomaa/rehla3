<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rehla\Api\Http\Resources\V1\WalletResource;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Wallet\Contracts\WalletReader;
use Rehla\Wallet\Data\WalletBalance;
use Rehla\Wallet\Exceptions\WalletNotFoundException;

final class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        try {
            $balance = app(WalletReader::class)->getBalanceByAccount($request->user()->id);
        } catch (WalletNotFoundException $e) {
            $wallet = app(OpenWallet::class)->execute($request->user()->id);
            $balance = new WalletBalance(walletId: $wallet->id, minor: 0, currency: 'SDG');
        }

        return response()->json([
            'data' => (new WalletResource($balance))->resolve(),
        ]);
    }

    public function entries(Request $request): JsonResponse
    {
        $wallet = app(WalletReader::class)->getWalletByAccount($request->user()->id);
        $entries = $wallet ? app(WalletReader::class)->listEntries($wallet->id) : [];

        return response()->json([
            'data' => $entries,
        ]);
    }
}
