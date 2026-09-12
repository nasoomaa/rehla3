<?php

declare(strict_types=1);

namespace Rehla\Identity\Queries;

use Rehla\Identity\Data\UserData;
use Rehla\Identity\Models\User;

final class FindCustomer
{
    public function findById(string $id): ?UserData
    {
        $user = User::with(['roles.abilities', 'staffProfile'])->find($id);

        return $user?->toUserData();
    }

    public function findByEmail(string $email): ?UserData
    {
        $user = User::with(['roles.abilities', 'staffProfile'])->where('email', strtolower(trim($email)))->first();

        return $user?->toUserData();
    }
}
