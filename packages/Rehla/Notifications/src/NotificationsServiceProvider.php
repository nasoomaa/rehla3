<?php

declare(strict_types=1);

namespace Rehla\Notifications;

use Illuminate\Support\ServiceProvider;
use Rehla\Notifications\Actions\AppendOutboxMessage;
use Rehla\Notifications\Console\ReplayDeadLetter;
use Rehla\Notifications\Console\RunOutboxWorker;
use Rehla\Notifications\Contracts\OutboxWriter;

final class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OutboxWriter::class, AppendOutboxMessage::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RunOutboxWorker::class,
                ReplayDeadLetter::class,
            ]);
        }
    }
}
