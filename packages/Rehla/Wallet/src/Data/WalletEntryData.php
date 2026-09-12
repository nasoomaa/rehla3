<?php

declare(strict_types=1);

namespace Rehla\Wallet\Data;

use DateTimeImmutable;
use Rehla\Wallet\Enums\LedgerEntryType;

final readonly class WalletEntryData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $walletId,
        public LedgerEntryType $type,
        public int $amountMinor,
        public int $balanceAfterMinor,
        public string $referenceType,
        public string $referenceId,
        public string $idempotencyKey,
        public ?string $reversesEntryId,
        public array $metadata,
        public DateTimeImmutable $createdAt,
    ) {}
}
