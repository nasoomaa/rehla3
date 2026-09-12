<?php

declare(strict_types=1);

namespace Rehla\TopUps;

use Illuminate\Support\ServiceProvider;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Documents\Actions\AttachDocument;
use Rehla\Documents\Contracts\OwnedDocuments;
use Rehla\TopUps\Actions\CreateBankAccount;
use Rehla\TopUps\Actions\DeactivateBankAccount;
use Rehla\TopUps\Actions\SubmitTopUp;
use Rehla\TopUps\Actions\UpdateBankAccount;

final class TopUpsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CreateBankAccount::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new CreateBankAccount($auditWriter);
        });

        $this->app->bind(UpdateBankAccount::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new UpdateBankAccount($auditWriter);
        });

        $this->app->bind(DeactivateBankAccount::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new DeactivateBankAccount($auditWriter);
        });

        $this->app->bind(SubmitTopUp::class, function ($app) {
            $ownedDocuments = $app->bound(OwnedDocuments::class) ? $app->make(OwnedDocuments::class) : null;
            $attachDocument = $app->bound(AttachDocument::class) ? $app->make(AttachDocument::class) : null;
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new SubmitTopUp($ownedDocuments, $attachDocument, $auditWriter);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
