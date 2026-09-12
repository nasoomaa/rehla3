<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Exceptions;

use DomainException;

class PriceChangedException extends DomainException
{
    public static function forService(string $serviceId, int $acceptedMinor, int $acceptedVersion, int $currentMinor, int $currentVersion): self
    {
        return new self("Price or version changed for service {$serviceId}. Accepted: {$acceptedMinor} (v{$acceptedVersion}), Current: {$currentMinor} (v{$currentVersion}).");
    }
}
