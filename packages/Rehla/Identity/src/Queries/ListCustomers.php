<?php

declare(strict_types=1);

namespace Rehla\Identity\Queries;

use Rehla\Identity\Data\UserData;
use Rehla\Identity\Models\User;

final class ListCustomers
{
    /**
     * @return list<UserData>
     */
    public function execute(): array
    {
        return User::whereDoesntHave('staffProfile')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (User $u): UserData => $u->toUserData())
            ->all();
    }
}
