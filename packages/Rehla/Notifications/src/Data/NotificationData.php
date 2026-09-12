<?php

declare(strict_types=1);

namespace Rehla\Notifications\Data;

use Carbon\CarbonImmutable;

final readonly class NotificationData
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $id,
        public string $userId,
        public string $type,
        public string $channel,
        public array $payload,
        public ?CarbonImmutable $readAt = null,
        public ?CarbonImmutable $createdAt = null,
    ) {}
}
