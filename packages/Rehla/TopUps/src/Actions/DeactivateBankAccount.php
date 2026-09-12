<?php

declare(strict_types=1);

namespace Rehla\TopUps\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\TopUps\Data\BankAccountData;
use Rehla\TopUps\Models\CompanyBankAccount;

final class DeactivateBankAccount
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(string $bankAccountId, ?string $actorId = null, string $actorType = 'staff'): BankAccountData
    {
        return DB::transaction(function () use ($bankAccountId, $actorId, $actorType): BankAccountData {
            /** @var CompanyBankAccount|null $account */
            $account = CompanyBankAccount::lockForUpdate()->find($bankAccountId);

            if (! $account) {
                throw new DomainException("Bank account not found: {$bankAccountId}");
            }

            $account->active = false;
            $account->save();

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'bank_account.deactivated',
                    subjectType: 'bank_account',
                    subjectId: $bankAccountId,
                    metadata: ['account_number' => (string) $account->account_number],
                ));
            }

            return $account->toData();
        });
    }
}
