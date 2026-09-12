<?php

declare(strict_types=1);

namespace Rehla\Identity\Data;

final readonly class ActorData
{
    public function __construct(
        public string $id,
        public string $type,            // 'customer' | 'staff'
        public ?string $mfaConfirmedAt, // ISO-8601 or null
        /** @var list<string> */
        public array $abilities,
    ) {}
}
