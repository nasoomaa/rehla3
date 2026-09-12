<?php

declare(strict_types=1);

namespace Rehla\Content\Data;

use DateTimeImmutable;

final readonly class LocalizedContentBlockView
{
    public function __construct(
        public string $key,
        public string $locale,
        public string $direction,
        public string $title,
        public string $body,
        public ?DateTimeImmutable $publishedAt = null,
    ) {}
}
