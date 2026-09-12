<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Exceptions;

use RuntimeException;

final class InvalidExecutionTransitionException extends RuntimeException
{
    public static function cannotTransition(string $from, string $to): self
    {
        return new self("Cannot transition execution from [{$from}] to [{$to}].");
    }
}
