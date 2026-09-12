<?php

declare(strict_types=1);

namespace Rehla\Orders;

use Illuminate\Support\ServiceProvider;
use Rehla\Orders\Actions\CreatePaidOrder;
use Rehla\Orders\Contracts\OrderWriter;
use Rehla\Orders\Queries\GetOwnedOrder;
use Rehla\Orders\Queries\ListOwnedOrders;

final class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderWriter::class, CreatePaidOrder::class);
        $this->app->bind(CreatePaidOrder::class);
        $this->app->bind(GetOwnedOrder::class);
        $this->app->bind(ListOwnedOrders::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
