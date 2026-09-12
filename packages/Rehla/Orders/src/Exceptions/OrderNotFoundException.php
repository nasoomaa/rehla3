<?php

declare(strict_types=1);

namespace Rehla\Orders\Exceptions;

use RuntimeException;

final class OrderNotFoundException extends RuntimeException
{
    public static function forId(string $orderId): self
    {
        return new self("Order [{$orderId}] not found.");
    }
}
