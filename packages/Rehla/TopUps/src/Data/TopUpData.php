<?php

declare(strict_types=1);

namespace Rehla\TopUps\Data;

use DateTimeImmutable;
use Rehla\TopUps\Enums\TopUpStatus;

final readonly class TopUpData
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $walletId,
        public string $bankAccountId,
        public int $amountMinor,
        public string $transactionReference,
        public string $normalizedReference,
        public string $receiptDocumentId,
        public TopUpStatus $status,
        public DateTimeImmutable $submittedAt,
        public ?string $reviewedBy = null,
        public ?DateTimeImmutable $decidedAt = null,
        public ?string $rejectionReason = null,
        public ?string $creditLedgerEntryId = null,
    ) {}
}
