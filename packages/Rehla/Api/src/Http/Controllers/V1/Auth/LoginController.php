<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rehla\Api\Errors\ProblemDetailsFactory;
use Rehla\Api\Http\Resources\V1\UserResource;
use Rehla\Identity\Actions\AuthenticateCustomer;
use Rehla\Identity\Actions\IssueCustomerToken;

final class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $userData = app(AuthenticateCustomer::class)->handle(
            email: $validated['email'],
            password: $validated['password'],
        );

        if ($userData === null) {
            return ProblemDetailsFactory::make(
                status: 401,
                code: 'INVALID_CREDENTIALS',
                detail: __('Invalid credentials provided.'),
            );
        }

        $deviceName = $validated['device_name'] ?? 'api';
        $token = app(IssueCustomerToken::class)->handle($userData->id, $deviceName);

        return response()->json([
            'data' => [
                'user' => (new UserResource($userData))->resolve(),
                'token' => $token,
            ],
        ]);
    }
}
