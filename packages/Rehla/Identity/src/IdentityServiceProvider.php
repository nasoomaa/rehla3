<?php

declare(strict_types=1);

namespace Rehla\Identity;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Rehla\Identity\Auth\StaffUserProvider;
use Rehla\Identity\Contracts\AuthorizesActor;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Identity\Models\User;
use Rehla\Identity\Services\ActorAuthorizer;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthorizesActor::class, ActorAuthorizer::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Auth::provider('staff_eloquent', function (Application $app, array $config): StaffUserProvider {
            return new StaffUserProvider($app['hash'], $config['model']);
        });

        Gate::before(function ($user, string $ability): ?bool {
            if ($user instanceof User) {
                $abilityEnum = AbilityName::tryFrom($ability);
                if ($abilityEnum !== null) {
                    return $this->app->make(AuthorizesActor::class)->allows($user->toActorData(), $abilityEnum);
                }
            }

            return null;
        });
    }
}
