<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Exceptions;

use RuntimeException;

final class ExecutionAccessDeniedException extends RuntimeException
{
    public static function manageDenied(): self
    {
        return new self('Actor is not authorized to manage executions.');
    }
}
