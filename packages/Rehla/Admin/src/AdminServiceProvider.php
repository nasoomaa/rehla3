<?php

declare(strict_types=1);

namespace Rehla\Admin;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Rehla\Admin\Http\Middleware\EnsureAdminAuthenticated;
use Rehla\Admin\Http\Middleware\EnsureStaffAbility;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'rehla-admin');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'rehla-admin');

        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('admin.auth', EnsureAdminAuthenticated::class);
        $router->aliasMiddleware('admin.ability', EnsureStaffAbility::class);
    }
}
