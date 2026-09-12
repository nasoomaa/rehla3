<?php

declare(strict_types=1);

namespace Rehla\Catalog;

use Illuminate\Support\ServiceProvider;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Catalog\Actions\ChangeServicePrice;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\DeactivateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Actions\UpdateServiceContent;
use Rehla\Catalog\Contracts\ServiceCatalog;
use Rehla\Catalog\Services\CatalogManager;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ServiceCatalog::class, CatalogManager::class);

        $this->app->bind(CreateService::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new CreateService($auditWriter);
        });

        $this->app->bind(PublishService::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new PublishService($auditWriter);
        });

        $this->app->bind(ChangeServicePrice::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new ChangeServicePrice($auditWriter);
        });

        $this->app->bind(DeactivateService::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new DeactivateService($auditWriter);
        });

        $this->app->bind(UpdateServiceContent::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new UpdateServiceContent($auditWriter);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
