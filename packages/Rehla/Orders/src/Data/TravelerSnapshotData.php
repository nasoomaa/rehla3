<?php

declare(strict_types=1);

namespace Rehla\Orders\Data;

final readonly class TravelerSnapshotData
{
    public function __construct(
        public string $fullName,
        public string $dateOfBirth,
        public string $gender,
        public string $passportNumber,
        public ?string $passportIssuedAt = null,
        public ?string $passportExpiresAt = null,
    ) {}
}
