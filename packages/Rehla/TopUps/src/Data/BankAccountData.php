<?php

declare(strict_types=1);

namespace Rehla\TopUps\Data;

use DateTimeImmutable;

final readonly class BankAccountData
{
    public function __construct(
        public string $id,
        public string $bankNameEn,
        public string $bankNameAr,
        public string $beneficiaryName,
        public string $accountNumber,
        public ?string $logoDocumentId,
        public bool $active,
        public int $sortOrder,
        public ?string $createdBy,
        public DateTimeImmutable $createdAt,
    ) {}
}
