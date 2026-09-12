<?php

declare(strict_types=1);

namespace Rehla\Travelers\Data;

use Rehla\Travelers\Enums\Gender;

final readonly class TravelerData
{
    public function __construct(
        public string $ownerId,
        public string $fullName,
        public string $dateOfBirth,
        public Gender $gender,
        public string $passportNumber,
        public string $passportIssuedAt,
        public string $passportExpiresAt,
        public ?string $id = null,
        public ?string $normalizedPassportNumber = null,
    ) {}
}
