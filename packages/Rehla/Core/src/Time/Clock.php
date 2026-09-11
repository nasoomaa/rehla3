<?php

declare(strict_types=1);

namespace Rehla\Core\Time;

use Carbon\CarbonImmutable;

interface Clock
{
    /**
     * Return the current point in time as an immutable Carbon instance.
     */
    public function now(): CarbonImmutable;
}
