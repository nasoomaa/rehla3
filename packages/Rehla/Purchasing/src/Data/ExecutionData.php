<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Data;

use DateTimeImmutable;

final readonly class ExecutionData
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $accountId,
        public string $travelerId,
        public string $serviceId,
        public string $formVersionId,
        public string $status,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $lastStatusAt = null,
    ) {}
}
