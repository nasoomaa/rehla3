<?php

declare(strict_types=1);

namespace Rehla\Web;

use Illuminate\Support\ServiceProvider;

final class WebServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'rehla-web');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'rehla-web');
    }
}
