<?php

declare(strict_types=1);

namespace Rehla\Wallet\Exceptions;

use DomainException;

final class WalletNotFoundException extends DomainException
{
    public static function forId(string $walletId): self
    {
        return new self("Wallet not found with ID: {$walletId}");
    }

    public static function forAccount(string $accountId): self
    {
        return new self("Wallet not found for account: {$accountId}");
    }
}
