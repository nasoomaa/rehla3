<?php

declare(strict_types=1);

namespace Rehla\Documents\Data;

use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\Documents\Enums\DocumentStatus;

final readonly class DocumentRef
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public DocumentPurpose $purpose,
        public DocumentStatus $status,
        public string $originalName,
        public int $sizeBytes,
        public string $sha256,
    ) {}
}
