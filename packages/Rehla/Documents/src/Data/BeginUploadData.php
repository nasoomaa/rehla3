<?php

declare(strict_types=1);

namespace Rehla\Documents\Data;

use Rehla\Documents\Enums\DocumentPurpose;

final readonly class BeginUploadData
{
    public function __construct(
        public string $ownerId,
        public DocumentPurpose $purpose,
        public int $lifetimeMinutes = 60,
    ) {}
}
