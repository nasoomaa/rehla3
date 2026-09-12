<?php

declare(strict_types=1);

namespace Rehla\Integrations;

use Illuminate\Support\ServiceProvider;
use Rehla\Integrations\Contracts\InquiryLinkBuilder;
use Rehla\Integrations\WhatsApp\WhatsAppInquiryLinkBuilder;

final class IntegrationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InquiryLinkBuilder::class,
            WhatsAppInquiryLinkBuilder::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
