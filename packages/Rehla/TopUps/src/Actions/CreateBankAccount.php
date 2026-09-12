<?php

declare(strict_types=1);

namespace Rehla\TopUps\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\TopUps\Data\BankAccountData;
use Rehla\TopUps\Data\CreateBankAccountData;
use Rehla\TopUps\Models\CompanyBankAccount;

final class CreateBankAccount
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(CreateBankAccountData $data, ?string $actorId = null, string $actorType = 'staff'): BankAccountData
    {
        return DB::transaction(function () use ($data, $actorId, $actorType): BankAccountData {
            $id = (string) Str::uuid();

            $account = CompanyBankAccount::create([
                'id' => $id,
                'bank_name_en' => $data->bankNameEn,
                'bank_name_ar' => $data->bankNameAr,
                'beneficiary_name' => $data->beneficiaryName,
                'account_number' => $data->accountNumber,
                'logo_document_id' => $data->logoDocumentId,
                'active' => true,
                'sort_order' => $data->sortOrder,
                'created_by' => $actorId,
            ]);

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'bank_account.created',
                    subjectType: 'bank_account',
                    subjectId: $id,
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
