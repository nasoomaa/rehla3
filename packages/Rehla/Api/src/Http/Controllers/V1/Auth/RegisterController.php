<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rehla\Api\Http\Resources\V1\UserResource;
use Rehla\Identity\Actions\IssueCustomerToken;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;

final class RegisterController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $userData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
        ));

        $deviceName = $validated['device_name'] ?? 'api';
        $token = app(IssueCustomerToken::class)->handle($userData->id, $deviceName);

        return response()->json([
            'data' => [
                'user' => (new UserResource($userData))->resolve(),
                'token' => $token,
            ],
        ], 201);
    }
}
