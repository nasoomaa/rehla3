<?php

declare(strict_types=1);

namespace Rehla\Orders\Data;

final readonly class ServiceSnapshotData
{
    /**
     * @param  array<string, mixed>  $descriptions
     * @param  array<string, mixed>  $requirements
     * @param  array<string, mixed>  $expectedDuration
     * @param  list<string>  $notes
     */
    public function __construct(
        public string $nameEn,
        public string $nameAr,
        public array $descriptions = [],
        public array $requirements = [],
        public array $expectedDuration = [],
        public array $notes = [],
    ) {}
}
