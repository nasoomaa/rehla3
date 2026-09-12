<?php

declare(strict_types=1);

namespace Rehla\Catalog\Queries;

use Rehla\Catalog\Data\ServicePricePoint;
use Rehla\Catalog\Models\ServicePriceHistory;

final class GetServicePriceHistory
{
    /**
     * @return array<int, ServicePricePoint>
     */
    public function execute(string $serviceId): array
    {
        return ServicePriceHistory::where('service_id', $serviceId)
            ->orderBy('version', 'asc')
            ->get()
            ->map(fn (ServicePriceHistory $h): ServicePricePoint => $h->toData())
            ->all();
    }
}
