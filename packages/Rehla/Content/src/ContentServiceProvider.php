<?php

declare(strict_types=1);

namespace Rehla\Content;

use Illuminate\Support\ServiceProvider;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Content\Actions\CreateContentBlock;
use Rehla\Content\Actions\PublishContentBlock;
use Rehla\Content\Actions\UpdateContentBlock;
use Rehla\Content\Queries\GetPublishedContentBlock;

final class ContentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GetPublishedContentBlock::class);

        $this->app->bind(CreateContentBlock::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new CreateContentBlock($auditWriter);
        });

        $this->app->bind(UpdateContentBlock::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new UpdateContentBlock($auditWriter);
        });

        $this->app->bind(PublishContentBlock::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new PublishContentBlock($auditWriter);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
