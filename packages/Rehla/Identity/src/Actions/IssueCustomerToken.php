<?php

declare(strict_types=1);

namespace Rehla\Identity\Actions;

use Rehla\Identity\Models\User;

final class IssueCustomerToken
{
    /**
     * @param  list<string>  $abilities
     */
    public function handle(string $userId, string $deviceName = 'api', array $abilities = ['customer']): string
    {
        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        return $user->createToken($deviceName, $abilities)->plainTextToken;
    }
}
