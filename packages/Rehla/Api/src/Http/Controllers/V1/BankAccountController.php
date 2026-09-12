<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rehla\Api\Http\Resources\V1\BankAccountResource;
use Rehla\TopUps\Queries\ListActiveBankAccounts;

final class BankAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $banks = app(ListActiveBankAccounts::class)->execute();

        return response()->json([
            'data' => BankAccountResource::collection($banks)->resolve(),
        ]);
    }
}
