<?php

declare(strict_types=1);

namespace Rehla\Identity\Data;

final readonly class UserData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $status,
        public ActorData $actor,
    ) {}
}
