<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rehla\Api\Http\Resources\V1\UserResource;

final class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => (new UserResource($request->user()))->resolve(),
        ]);
    }
}
