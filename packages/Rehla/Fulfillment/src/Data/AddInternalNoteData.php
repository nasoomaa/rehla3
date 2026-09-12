<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Data;

use Rehla\Identity\Data\ActorData;

final readonly class AddInternalNoteData
{
    public function __construct(
        public string $executionId,
        public ActorData $actor,
        public string $body,
    ) {}
}
