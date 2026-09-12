<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Data;

use DateTimeImmutable;

final readonly class StatusHistoryEntryData
{
    public function __construct(
        public string $id,
        public ?string $fromStatus,
        public string $toStatus,
        public string $actorType,
        public ?string $actorId,
        public ?string $reason,
        public DateTimeImmutable $createdAt,
    ) {}
}
