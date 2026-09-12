<?php

declare(strict_types=1);

namespace Rehla\Catalog\Contracts;

use Rehla\Catalog\Data\ServiceQuote;
use Rehla\Catalog\Data\ServiceSnapshot;

interface ServiceCatalog
{
    public function currentQuote(string $serviceId): ServiceQuote;

    public function serviceSnapshot(string $serviceId): ?ServiceSnapshot;

    public function isAvailable(string $serviceId): bool;
}
