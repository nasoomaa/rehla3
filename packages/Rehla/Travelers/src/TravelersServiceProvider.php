<?php

declare(strict_types=1);

namespace Rehla\Travelers;

use Illuminate\Support\ServiceProvider;
use Rehla\Travelers\Contracts\ResolvesTravelerSnapshots;
use Rehla\Travelers\Services\TravelerSnapshotResolver;

final class TravelersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ResolvesTravelerSnapshots::class, TravelerSnapshotResolver::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
