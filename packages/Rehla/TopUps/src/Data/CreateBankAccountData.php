<?php

declare(strict_types=1);

namespace Rehla\TopUps\Data;

final readonly class CreateBankAccountData
{
    public function __construct(
        public string $bankNameEn,
        public string $bankNameAr,
        public string $beneficiaryName,
        public string $accountNumber,
        public ?string $logoDocumentId = null,
        public int $sortOrder = 0,
    ) {}
}
