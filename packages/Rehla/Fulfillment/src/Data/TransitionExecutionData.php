<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Data;

use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Identity\Data\ActorData;

final readonly class TransitionExecutionData
{
    public function __construct(
        public string $executionId,
        public ExecutionStatus $toStatus,
        public ActorData $actor,
        public ?string $reason = null,
    ) {}
}
