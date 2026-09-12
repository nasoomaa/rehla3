<?php

declare(strict_types=1);

namespace Rehla\Forms\Data;

use DateTimeImmutable;
use Rehla\Forms\Enums\FormVersionStatus;

final readonly class PublishedFormData
{
    /**
     * @param  array<int, FormFieldData>  $fields
     */
    public function __construct(
        public string $id,
        public string $serviceId,
        public int $version,
        public FormVersionStatus $status,
        public ?string $checksum,
        public array $fields,
        public ?DateTimeImmutable $publishedAt = null,
        public ?string $publishedBy = null,
    ) {}
}
