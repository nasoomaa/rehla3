<?php

declare(strict_types=1);

namespace Rehla\TopUps\Exceptions;

use RuntimeException;

final class TopUpAccessDeniedException extends RuntimeException
{
    public static function reviewDenied(): self
    {
        return new self('Actor is not authorized to review top-up requests.');
    }
}
