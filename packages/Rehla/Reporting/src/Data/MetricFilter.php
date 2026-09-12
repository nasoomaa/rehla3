<?php

declare(strict_types=1);

namespace Rehla\Reporting\Data;

use Carbon\CarbonImmutable;

final readonly class MetricFilter
{
    public function __construct(
        public CarbonImmutable $fromUtc,
        public CarbonImmutable $toUtc,
        public string $displayTimezone = 'Africa/Khartoum',
    ) {}

    public static function forPeriod(
        CarbonImmutable|string $from,
        CarbonImmutable|string $to,
        string $timezone = 'Africa/Khartoum',
    ): self {
        $fromUtc = is_string($from)
            ? CarbonImmutable::parse($from, 'UTC')->startOfDay()
            : $from->setTimezone('UTC');

        $toUtc = is_string($to)
            ? CarbonImmutable::parse($to, 'UTC')->startOfDay()
            : $to->setTimezone('UTC');

        return new self($fromUtc, $toUtc, $timezone);
    }
}
