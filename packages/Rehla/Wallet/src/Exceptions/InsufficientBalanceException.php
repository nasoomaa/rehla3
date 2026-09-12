<?php

declare(strict_types=1);

namespace Rehla\Wallet\Exceptions;

use DomainException;

final class InsufficientBalanceException extends DomainException
{
    public static function forWallet(string $walletId, int $requiredMinor, int $currentMinor): self
    {
        return new self("Wallet {$walletId} has insufficient balance: required {$requiredMinor} SDG minor, available {$currentMinor} SDG minor.");
    }
}
