<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Exceptions;

use DomainException;

class OperationInProgressException extends DomainException
{
    public static function forKey(string $key): self
    {
        return new self("Operation with idempotency key '{$key}' is currently in progress.");
    }
}
