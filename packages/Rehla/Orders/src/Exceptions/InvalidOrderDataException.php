<?php

declare(strict_types=1);

namespace Rehla\Orders\Exceptions;

use InvalidArgumentException;

final class InvalidOrderDataException extends InvalidArgumentException
{
    public static function invalidPriceOrPayment(): self
    {
        return new self('Order price must be positive and exactly match the amount paid.');
    }

    public static function unsupportedCurrency(string $currency): self
    {
        return new self("Currency [{$currency}] is not supported. Only SDG is allowed.");
    }
}
