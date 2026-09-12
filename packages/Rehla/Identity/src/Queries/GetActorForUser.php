<?php

declare(strict_types=1);

namespace Rehla\Identity\Queries;

use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Models\User;

final class GetActorForUser
{
    public function handle(string $userId): ?ActorData
    {
        /** @var User|null $user */
        $user = User::find($userId);

        return $user?->toActorData();
    }
}
