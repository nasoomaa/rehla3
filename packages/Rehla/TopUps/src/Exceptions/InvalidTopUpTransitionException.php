<?php

declare(strict_types=1);

namespace Rehla\TopUps\Exceptions;

use RuntimeException;

final class InvalidTopUpTransitionException extends RuntimeException
{
    public static function cannotTransition(string $from, string $to): self
    {
        return new self("Cannot transition top-up request from {$from} to {$to}.");
    }
}
