<?php

declare(strict_types=1);

namespace Rehla\Content\Data;

final readonly class CreateContentBlockData
{
    public function __construct(
        public string $key,
        public string $titleEn,
        public string $titleAr,
        public string $bodyEn = '',
        public string $bodyAr = '',
    ) {}
}
