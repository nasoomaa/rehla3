<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Exceptions;

use DomainException;

class ServiceNotAvailableException extends DomainException
{
    public static function forService(string $serviceId): self
    {
        return new self("Service {$serviceId} is currently not available.");
    }
}
