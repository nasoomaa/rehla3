<?php

declare(strict_types=1);

namespace Rehla\Catalog\Queries;

use Rehla\Catalog\Data\ServiceQuote;
use Rehla\Catalog\Enums\ServiceStatus;
use Rehla\Catalog\Models\Service;

final class GetCurrentServiceQuote
{
    public function execute(string $serviceId): ServiceQuote
    {
        /** @var Service|null $service */
        $service = Service::find($serviceId);

        if (! $service) {
            return new ServiceQuote(
                serviceId: $serviceId,
                priceMinor: 0,
                currency: 'SDG',
                quoteVersion: 0,
                available: false,
            );
        }

        $isAvailable = $service->status === ServiceStatus::Published;

        return new ServiceQuote(
            serviceId: (string) $service->id,
            priceMinor: (int) $service->current_price_minor,
            currency: (string) $service->currency,
            quoteVersion: (int) $service->price_version,
            available: $isAvailable,
        );
    }
}
