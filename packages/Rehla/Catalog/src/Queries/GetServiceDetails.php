<?php

declare(strict_types=1);

namespace Rehla\Catalog\Queries;

use Rehla\Catalog\Data\ServiceData;
use Rehla\Catalog\Models\Service;

final class GetServiceDetails
{
    public function execute(string $serviceId): ?ServiceData
    {
        /** @var Service|null $service */
        $service = Service::find($serviceId);

        return $service?->toData();
    }

    public function executeBySlug(string $slug): ?ServiceData
    {
        /** @var Service|null $service */
        $service = Service::where('slug', $slug)->first();

        return $service?->toData();
    }
}
