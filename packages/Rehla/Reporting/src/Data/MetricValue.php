<?php

declare(strict_types=1);

namespace Rehla\Reporting\Data;

use Rehla\Reporting\Enums\MetricName;

final readonly class MetricValue
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public MetricName $name,
        public mixed $value,
        public ?string $unit = null,
        public array $metadata = [],
    ) {}
}
