<?php

declare(strict_types=1);

namespace Rehla\Catalog\Exceptions;

use DomainException;

final class ServicePublishingValidationException extends DomainException
{
    public static function missingRequirements(string $serviceId): self
    {
        return new self("Service '{$serviceId}' cannot be published: missing name, descriptions, price, or requirements.");
    }
}
