<?php

declare(strict_types=1);

namespace Rehla\Orders\Data;

use DateTimeImmutable;

final readonly class OrderSummaryData
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $serviceId,
        public string $travelerId,
        public int $priceMinor,
        public string $currency,
        public string $financialStatus,
        public DateTimeImmutable $createdAt,
        public string $serviceNameEn,
        public string $serviceNameAr,
        public string $travelerFullName,
    ) {}
}
