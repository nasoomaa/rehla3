<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Exceptions;

use DomainException;

class IdempotencyKeyReusedException extends DomainException
{
    public static function forKey(string $key): self
    {
        return new self("Idempotency key '{$key}' was already used with a different request payload.");
    }
}
