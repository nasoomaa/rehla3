<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Exceptions;

use RuntimeException;

final class CustomerActionNotFoundException extends RuntimeException
{
    public static function forId(string $actionRequestId): self
    {
        return new self("Customer action request [{$actionRequestId}] not found.");
    }
}
