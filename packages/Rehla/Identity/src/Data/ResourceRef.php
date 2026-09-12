<?php

declare(strict_types=1);

namespace Rehla\Identity\Data;

final readonly class ResourceRef
{
    public function __construct(
        public string $type,
        public string $id,
    ) {}
}
