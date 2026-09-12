<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Exceptions;

use RuntimeException;

final class ExecutionNotFoundException extends RuntimeException
{
    public static function forId(string $id): self
    {
        return new self("Execution [{$id}] not found.");
    }
}
