<?php

declare(strict_types=1);

namespace Rehla\Identity\Data;

use Rehla\Identity\Enums\ActorType;

final readonly class ActorData
{
    public string $type;

    /**
     * @param  list<string>  $abilities
     */
    public function __construct(
        public string $id,
        string|ActorType $type,
        public ?string $mfaConfirmedAt = null,
        public array $abilities = [],
    ) {
        $this->type = $type instanceof ActorType ? $type->value : $type;
    }
}
