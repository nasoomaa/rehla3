<?php

declare(strict_types=1);

namespace Rehla\Identity\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class StaffUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        $user = parent::retrieveById($identifier);

        if ($user && method_exists($user, 'staffProfile') && $user->staffProfile()->doesntExist()) {
            return null;
        }

        return $user;
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $user = parent::retrieveByCredentials($credentials);

        if ($user && method_exists($user, 'staffProfile') && $user->staffProfile()->doesntExist()) {
            return null;
        }

        return $user;
    }
}
