<?php

declare(strict_types=1);

namespace Rehla\Fulfillment;

use Illuminate\Support\ServiceProvider;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Documents\Actions\AttachDocument;
use Rehla\Documents\Contracts\OwnedDocuments;
use Rehla\Fulfillment\Actions\AddInternalNote;
use Rehla\Fulfillment\Actions\CreateExecution;
use Rehla\Fulfillment\Actions\RequestCustomerAction;
use Rehla\Fulfillment\Actions\RespondToCustomerAction;
use Rehla\Fulfillment\Actions\TransitionExecution;
use Rehla\Fulfillment\Queries\GetExecutionForOperations;
use Rehla\Fulfillment\Queries\GetOwnedExecution;
use Rehla\Identity\Contracts\AuthorizesActor;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Purchasing\Contracts\ExecutionCreator;

final class FulfillmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExecutionCreator::class, CreateExecution::class);
        $this->app->bind(CreateExecution::class);

        $this->app->bind(TransitionExecution::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;
            $outboxWriter = $app->bound(OutboxWriter::class) ? $app->make(OutboxWriter::class) : null;
            $authorizer = $app->bound(AuthorizesActor::class) ? $app->make(AuthorizesActor::class) : null;

            return new TransitionExecution($auditWriter, $outboxWriter, $authorizer);
        });

        $this->app->bind(AddInternalNote::class);

        $this->app->bind(RequestCustomerAction::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;
            $outboxWriter = $app->bound(OutboxWriter::class) ? $app->make(OutboxWriter::class) : null;

            return new RequestCustomerAction($auditWriter, $outboxWriter);
        });

        $this->app->bind(RespondToCustomerAction::class, function ($app) {
            $ownedDocuments = $app->bound(OwnedDocuments::class) ? $app->make(OwnedDocuments::class) : null;
            $attachDocument = $app->make(AttachDocument::class);
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;
            $outboxWriter = $app->bound(OutboxWriter::class) ? $app->make(OutboxWriter::class) : null;

            return new RespondToCustomerAction($ownedDocuments, $attachDocument, $auditWriter, $outboxWriter);
        });

        $this->app->bind(GetOwnedExecution::class);
        $this->app->bind(GetExecutionForOperations::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
