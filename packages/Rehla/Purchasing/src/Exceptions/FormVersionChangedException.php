<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Exceptions;

use DomainException;

class FormVersionChangedException extends DomainException
{
    public static function forVersion(string $serviceId, string $expectedId, ?string $currentId): self
    {
        return new self("Form version changed or mismatch for service {$serviceId}. Expected {$expectedId}, current is ".($currentId ?? 'none').'.');
    }
}
