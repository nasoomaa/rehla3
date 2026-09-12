<?php

declare(strict_types=1);

namespace Rehla\Integrations\Data;

final readonly class InquiryLink
{
    public function __construct(
        public string $url,
        public string $text,
        public string $locale,
    ) {}
}
