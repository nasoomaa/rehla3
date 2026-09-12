<?php

declare(strict_types=1);

namespace Rehla\Wallet\Data;

final readonly class DebitResult
{
    public function __construct(
        public string $walletId,
        public string $entryId,
        public int $amountMinor,
        public int $balanceAfterMinor,
        public bool $isReplay = false,
    ) {}
}
