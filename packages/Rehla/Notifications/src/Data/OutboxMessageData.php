<?php

declare(strict_types=1);

namespace Rehla\Notifications\Data;

final readonly class OutboxMessageData
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $eventName,
        public string $aggregateType,
        public string $aggregateId,
        public array $payload,
        public int $payloadVersion = 1,
        public ?string $deduplicationKey = null,
    ) {}
}
