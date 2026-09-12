<?php

declare(strict_types=1);

namespace Rehla\Identity\Data;

final readonly class RegisterCustomerData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
