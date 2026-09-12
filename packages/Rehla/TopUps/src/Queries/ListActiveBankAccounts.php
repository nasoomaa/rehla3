<?php

declare(strict_types=1);

namespace Rehla\TopUps\Queries;

use Rehla\TopUps\Data\BankAccountData;
use Rehla\TopUps\Models\CompanyBankAccount;

final class ListActiveBankAccounts
{
    /**
     * @return array<int, BankAccountData>
     */
    public function execute(): array
    {
        return CompanyBankAccount::where('active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn (CompanyBankAccount $b): BankAccountData => $b->toData())
            ->all();
    }
}
