<?php

declare(strict_types=1);

namespace Rehla\Identity\Queries;

use Rehla\Identity\Data\UserData;
use Rehla\Identity\Models\User;

final class ListStaffUsers
{
    /**
     * @return list<UserData>
     */
    public function execute(): array
    {
        return User::whereHas('staffProfile')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (User $u): UserData => $u->toUserData())
            ->all();
    }
}
