<?php

declare(strict_types=1);

namespace Rehla\Identity\Actions;

use Rehla\Identity\Models\Role;
use Rehla\Identity\Models\User;

final class RevokeRole
{
    public function handle(string $userId, string $roleName): void
    {
        $user = User::findOrFail($userId);
        $role = Role::where('name', $roleName)->first();

        if ($role !== null) {
            $user->roles()->detach($role->id);
        }
    }
}
