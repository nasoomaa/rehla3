<?php

declare(strict_types=1);

namespace Rehla\TopUps\Data;

final readonly class SubmitTopUpData
{
    public function __construct(
        public string $accountId,
        public string $walletId,
        public string $bankAccountId,
        public int $amountMinor,
        public string $transactionReference,
        public string $receiptDocumentId,
    ) {}
}
