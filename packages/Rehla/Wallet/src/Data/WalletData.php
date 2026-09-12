<?php

declare(strict_types=1);

namespace Rehla\Wallet\Data;

use DateTimeImmutable;

final readonly class WalletData
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $currency,
        public int $balanceMinor,
        public int $lockVersion,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}
}
