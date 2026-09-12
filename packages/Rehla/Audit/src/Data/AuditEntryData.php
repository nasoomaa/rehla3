<?php

declare(strict_types=1);

namespace Rehla\Audit\Data;

use DateTimeImmutable;

final readonly class AuditEntryData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $actorType,
        public ?string $actorId,
        public string $action,
        public string $subjectType,
        public ?string $subjectId,
        public array $metadata = [],
        public ?string $ipHash = null,
        public ?string $userAgentHash = null,
        public ?DateTimeImmutable $occurredAt = null,
    ) {}
}
