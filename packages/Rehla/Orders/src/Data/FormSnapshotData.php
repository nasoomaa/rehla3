<?php

declare(strict_types=1);

namespace Rehla\Orders\Data;

final readonly class FormSnapshotData
{
    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $answers
     */
    public function __construct(
        public string $formVersionId,
        public int $formVersion,
        public string $formChecksum,
        public array $schema,
        public array $answers,
    ) {}
}
