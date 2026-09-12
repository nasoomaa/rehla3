<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rehla\Api\Http\Resources\V1\TopUpResource;
use Rehla\TopUps\Actions\SubmitTopUp;
use Rehla\TopUps\Data\SubmitTopUpData;
use Rehla\TopUps\Queries\ListOwnedTopUps;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Wallet\Contracts\WalletReader;

final class TopUpController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $topUps = app(ListOwnedTopUps::class)->execute($request->user()->id);

        return response()->json([
            'data' => TopUpResource::collection($topUps)->resolve(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_account_id' => ['required', 'string'],
            'amount_minor' => ['required', 'integer', 'min:500000'],
            'transaction_reference' => ['required', 'string', 'max:255'],
            'receipt_document_id' => ['required', 'string', 'uuid'],
        ]);

        $accountId = $request->user()->id;
        $wallet = app(WalletReader::class)->getWalletByAccount($accountId)
            ?? app(OpenWallet::class)->execute($accountId);

        $topUp = app(SubmitTopUp::class)->execute(new SubmitTopUpData(
            accountId: $accountId,
            walletId: $wallet->id,
            bankAccountId: $validated['bank_account_id'],
            amountMinor: $validated['amount_minor'],
            transactionReference: $validated['transaction_reference'],
            receiptDocumentId: $validated['receipt_document_id'],
        ));

        return response()->json([
            'data' => (new TopUpResource($topUp))->resolve(),
        ], 201);
    }
}
