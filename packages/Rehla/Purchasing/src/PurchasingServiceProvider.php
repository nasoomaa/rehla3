<?php

declare(strict_types=1);

namespace Rehla\Purchasing;

use Illuminate\Support\ServiceProvider;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Catalog\Contracts\ServiceCatalog;
use Rehla\Documents\Actions\AttachDocument;
use Rehla\Documents\Contracts\OwnedDocuments;
use Rehla\Forms\Contracts\FormSubmissionValidator;
use Rehla\Forms\Queries\GetPublishedForm;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Orders\Contracts\OrderWriter;
use Rehla\Purchasing\Actions\SubmitOrder;
use Rehla\Purchasing\Contracts\ExecutionCreator;
use Rehla\Travelers\Queries\GetOwnedTravelerSnapshot;
use Rehla\Wallet\Contracts\WalletDebitor;
use Rehla\Wallet\Contracts\WalletReader;

final class PurchasingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubmitOrder::class, function ($app) {
            return new SubmitOrder(
                catalog: $app->make(ServiceCatalog::class),
                getPublishedForm: $app->make(GetPublishedForm::class),
                formSubmissionValidator: $app->make(FormSubmissionValidator::class),
                getOwnedTravelerSnapshot: $app->make(GetOwnedTravelerSnapshot::class),
                walletReader: $app->make(WalletReader::class),
                walletDebitor: $app->make(WalletDebitor::class),
                orderWriter: $app->make(OrderWriter::class),
                executionCreator: $app->make(ExecutionCreator::class),
                ownedDocuments: $app->bound(OwnedDocuments::class) ? $app->make(OwnedDocuments::class) : null,
                attachDocument: $app->bound(AttachDocument::class) ? $app->make(AttachDocument::class) : null,
                auditWriter: $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null,
                outboxWriter: $app->bound(OutboxWriter::class) ? $app->make(OutboxWriter::class) : null,
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
