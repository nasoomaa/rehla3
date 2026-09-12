<?php

declare(strict_types=1);

namespace Rehla\TopUps\Data;

use Rehla\Identity\Data\ActorData;

final readonly class ApproveTopUpData
{
    public function __construct(
        public string $topUpId,
        public ActorData $actor,
    ) {}
}
