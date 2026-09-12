<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Data;

use DateTimeImmutable;

final readonly class CustomerActionRequestData
{
    public function __construct(
        public string $id,
        public string $executionId,
        public string $descriptionEn,
        public string $descriptionAr,
        public ?string $requiredDocumentPurpose,
        public string $status,
        public ?DateTimeImmutable $dueAt,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $resolvedAt = null,
    ) {}
}
