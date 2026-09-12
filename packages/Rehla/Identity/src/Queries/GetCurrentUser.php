<?php

declare(strict_types=1);

namespace Rehla\Identity\Queries;

use Illuminate\Contracts\Auth\Guard;
use Rehla\Identity\Data\UserData;
use Rehla\Identity\Models\User;

final class GetCurrentUser
{
    public function __construct(private Guard $auth) {}

    public function handle(): ?UserData
    {
        /** @var User|null $user */
        $user = $this->auth->user();

        return $user?->toUserData();
    }
}
