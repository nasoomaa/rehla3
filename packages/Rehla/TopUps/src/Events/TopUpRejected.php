<?php

declare(strict_types=1);

namespace Rehla\TopUps\Events;

use Rehla\TopUps\Data\TopUpData;

final readonly class TopUpRejected
{
    public function __construct(
        public TopUpData $topUp,
    ) {}
}
