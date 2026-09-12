<?php

declare(strict_types=1);

namespace Rehla\Travelers\Data;

use Rehla\Travelers\Enums\Gender;

final readonly class TravelerSnapshot
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $fullName,
        public string $dateOfBirth,
        public Gender $gender,
        public string $passportNumber,
        public string $passportIssuedAt,
        public string $passportExpiresAt,
    ) {}
}
