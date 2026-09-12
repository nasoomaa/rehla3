<?php

declare(strict_types=1);

namespace Rehla\Catalog\Data;

use DateTimeImmutable;

final readonly class ServicePricePoint
{
    public function __construct(
        public string $id,
        public string $serviceId,
        public int $priceMinor,
        public string $currency,
        public int $version,
        public ?string $changedBy,
        public DateTimeImmutable $effectiveAt,
        public DateTimeImmutable $createdAt,
    ) {}
}
