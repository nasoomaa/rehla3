<?php

declare(strict_types=1);

namespace Rehla\Reporting;

use Illuminate\Support\ServiceProvider;
use Rehla\Reporting\Queries\GetProductMetrics;

final class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GetProductMetrics::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
