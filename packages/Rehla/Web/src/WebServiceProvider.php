<?php

declare(strict_types=1);

namespace Rehla\Web;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Rehla\Web\Livewire\Account\CustomerActionResponse;
use Rehla\Web\Livewire\Account\OrderCheckout;
use Rehla\Web\Livewire\Account\OrderShow;
use Rehla\Web\Livewire\Account\TopUpCreate;

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

        if (class_exists(Livewire::class)) {
            Livewire::component('top-up-create', TopUpCreate::class);
            Livewire::component('order-checkout', OrderCheckout::class);
            Livewire::component('order-show', OrderShow::class);
            Livewire::component('customer-action-response', CustomerActionResponse::class);
        }
    }
}
