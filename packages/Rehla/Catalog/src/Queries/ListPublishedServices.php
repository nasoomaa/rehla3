<?php

declare(strict_types=1);

namespace Rehla\Catalog\Queries;

use Rehla\Catalog\Data\ServiceData;
use Rehla\Catalog\Enums\ServiceStatus;
use Rehla\Catalog\Models\Service;

final class ListPublishedServices
{
    /**
     * @return array<int, ServiceData>
     */
    public function execute(): array
    {
        return Service::where('status', ServiceStatus::Published)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn (Service $s): ServiceData => $s->toData())
            ->all();
    }
}
