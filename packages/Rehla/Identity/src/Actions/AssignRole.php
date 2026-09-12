<?php

declare(strict_types=1);

namespace Rehla\Identity\Actions;

use Illuminate\Support\Str;
use Rehla\Identity\Models\Role;
use Rehla\Identity\Models\User;

final class AssignRole
{
    public function handle(string $userId, string $roleName): void
    {
        $user = User::findOrFail($userId);

        if ($user->staffProfile()->doesntExist()) {
            throw new \InvalidArgumentException("User {$userId} does not have a staff profile.");
        }

        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['id' => (string) Str::uuid(), 'label' => ucfirst($roleName)]
        );

        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
