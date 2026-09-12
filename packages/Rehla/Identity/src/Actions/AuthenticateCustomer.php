<?php

declare(strict_types=1);

namespace Rehla\Identity\Actions;

use Illuminate\Support\Facades\Hash;
use Rehla\Identity\Data\UserData;
use Rehla\Identity\Models\User;

final class AuthenticateCustomer
{
    public function handle(string $email, string $password): ?UserData
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->status !== 'active') {
            return null;
        }

        return $user->toUserData();
    }
}
