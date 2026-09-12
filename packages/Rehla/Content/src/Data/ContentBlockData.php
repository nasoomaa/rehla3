<?php

declare(strict_types=1);

namespace Rehla\Content\Data;

use DateTimeImmutable;
use Rehla\Content\Enums\ContentStatus;

final readonly class ContentBlockData
{
    public function __construct(
        public string $id,
        public string $key,
        public string $titleEn,
        public string $titleAr,
        public string $bodyEn,
        public string $bodyAr,
        public ContentStatus $status,
        public ?DateTimeImmutable $publishedAt = null,
        public ?string $createdBy = null,
        public ?string $updatedBy = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {}
}
