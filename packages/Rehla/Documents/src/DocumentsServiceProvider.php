<?php

declare(strict_types=1);

namespace Rehla\Documents;

use Illuminate\Support\ServiceProvider;
use Rehla\Documents\Contracts\DocumentScanner;
use Rehla\Documents\Contracts\OwnedDocuments;
use Rehla\Documents\Infrastructure\ClamAvDocumentScanner;
use Rehla\Documents\Services\OwnedDocumentsService;

final class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentScanner::class, ClamAvDocumentScanner::class);
        $this->app->bind(OwnedDocuments::class, OwnedDocumentsService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
