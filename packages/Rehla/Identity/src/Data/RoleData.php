<?php

declare(strict_types=1);

namespace Rehla\Identity\Data;

final readonly class RoleData
{
    /**
     * @param  list<string>  $abilities
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $label,
        public array $abilities = [],
    ) {}
}
