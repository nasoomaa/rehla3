<?php

declare(strict_types=1);

namespace Rehla\TopUps\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\TopUps\Data\BankAccountData;
use Rehla\TopUps\Data\CreateBankAccountData;
use Rehla\TopUps\Models\CompanyBankAccount;

final class UpdateBankAccount
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(string $bankAccountId, CreateBankAccountData $data, ?string $actorId = null, string $actorType = 'staff'): BankAccountData
    {
        return DB::transaction(function () use ($bankAccountId, $data, $actorId, $actorType): BankAccountData {
            /** @var CompanyBankAccount|null $account */
            $account = CompanyBankAccount::lockForUpdate()->find($bankAccountId);

            if (! $account) {
                throw new DomainException("Bank account not found: {$bankAccountId}");
            }

            $account->update([
                'bank_name_en' => $data->bankNameEn,
                'bank_name_ar' => $data->bankNameAr,
                'beneficiary_name' => $data->beneficiaryName,
                'account_number' => $data->accountNumber,
                'logo_document_id' => $data->logoDocumentId,
                'sort_order' => $data->sortOrder,
            ]);

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'bank_account.updated',
                    subjectType: 'bank_account',
                    subjectId: $bankAccountId,
                    metadata: [
                        'bank_name_en' => $data->bankNameEn,
                        'account_number' => $data->accountNumber,
                    ],
                ));
            }

            return $account->toData();
        });
    }
}
