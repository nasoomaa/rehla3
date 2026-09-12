<?php

declare(strict_types=1);

namespace Rehla\TopUps\Exceptions;

use DomainException;

final class InactiveBankAccountException extends DomainException
{
    public static function forId(string $bankAccountId): self
    {
        return new self("Bank account {$bankAccountId} is not active and cannot receive new top-ups.");
    }
}
