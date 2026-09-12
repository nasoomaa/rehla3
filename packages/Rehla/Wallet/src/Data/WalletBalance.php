<?php

declare(strict_types=1);

namespace Rehla\Wallet\Data;

final readonly class WalletBalance
{
    public function __construct(
        public string $walletId,
        public int $minor,
        public string $currency = 'SDG',
    ) {}
}
