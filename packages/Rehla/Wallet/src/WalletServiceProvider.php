<?php

declare(strict_types=1);

namespace Rehla\Wallet;

use Illuminate\Support\ServiceProvider;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Wallet\Contracts\WalletCreditor;
use Rehla\Wallet\Contracts\WalletDebitor;
use Rehla\Wallet\Contracts\WalletReader;
use Rehla\Wallet\Services\WalletManager;

final class WalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WalletManager::class);
        $this->app->singleton(WalletReader::class, WalletManager::class);
        $this->app->singleton(WalletCreditor::class, WalletManager::class);
        $this->app->singleton(WalletDebitor::class, WalletManager::class);

        $this->app->bind(OpenWallet::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new OpenWallet($auditWriter);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
