<?php

declare(strict_types=1);

namespace Rehla\TopUps\Exceptions;

use DomainException;

final class InvalidTopUpAmountException extends DomainException
{
    public static function belowMinimum(int $amountMinor, int $minMinor = 500000): self
    {
        return new self("Top-up amount of {$amountMinor} SDG minor is below the minimum required amount of {$minMinor} SDG minor.");
    }
}
