<?php

declare(strict_types=1);

namespace Rehla\Forms;

use Illuminate\Support\ServiceProvider;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Forms\Actions\CreateFormDraft;
use Rehla\Forms\Actions\PublishFormVersion;
use Rehla\Forms\Actions\UpdateFormDraft;
use Rehla\Forms\Contracts\FormSubmissionValidator;
use Rehla\Forms\Services\FormValidatorService;

final class FormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FormSubmissionValidator::class, FormValidatorService::class);

        $this->app->bind(CreateFormDraft::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new CreateFormDraft($auditWriter);
        });

        $this->app->bind(UpdateFormDraft::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new UpdateFormDraft($auditWriter);
        });

        $this->app->bind(PublishFormVersion::class, function ($app) {
            $auditWriter = $app->bound(AuditWriter::class) ? $app->make(AuditWriter::class) : null;

            return new PublishFormVersion($auditWriter);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
