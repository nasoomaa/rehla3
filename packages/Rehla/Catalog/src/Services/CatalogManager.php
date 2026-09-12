<?php

declare(strict_types=1);

namespace Rehla\Catalog\Services;

use Rehla\Catalog\Contracts\ServiceCatalog;
use Rehla\Catalog\Data\ServiceQuote;
use Rehla\Catalog\Data\ServiceSnapshot;
use Rehla\Catalog\Enums\ServiceStatus;
use Rehla\Catalog\Models\Service;
use Rehla\Catalog\Queries\GetCurrentServiceQuote;

final class CatalogManager implements ServiceCatalog
{
    public function __construct(
        private readonly GetCurrentServiceQuote $getQuote,
    ) {}

    public function currentQuote(string $serviceId): ServiceQuote
    {
        return $this->getQuote->execute($serviceId);
    }

    public function serviceSnapshot(string $serviceId): ?ServiceSnapshot
    {
        /** @var Service|null $service */
        $service = Service::find($serviceId);

        return $service?->toSnapshot();
    }

    public function isAvailable(string $serviceId): bool
    {
        /** @var Service|null $service */
        $service = Service::find($serviceId);

        return $service !== null && $service->status === ServiceStatus::Published;
    }
}
