<?php

declare(strict_types=1);

namespace Rehla\Wallet\Data;

final readonly class CreditWalletData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $walletId,
        public int $amountMinor,
        public string $referenceType,
        public string $referenceId,
        public string $idempotencyKey,
        public array $metadata = [],
    ) {}
}
