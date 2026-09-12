<?php

declare(strict_types=1);

namespace Rehla\Catalog\Exceptions;

use InvalidArgumentException;

final class InvalidPriceException extends InvalidArgumentException
{
    public static function negativePrice(int $priceMinor): self
    {
        return new self("Service price cannot be negative: {$priceMinor}");
    }
}
