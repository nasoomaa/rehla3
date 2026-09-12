<?php

declare(strict_types=1);

namespace Rehla\TopUps\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Rehla\TopUps\Data\BankAccountData;

final class CompanyBankAccount extends Model
{
    use HasUuids;

    protected $table = 'company_bank_accounts';

    protected $fillable = [
        'id',
        'bank_name_en',
        'bank_name_ar',
        'beneficiary_name',
        'account_number',
        'logo_document_id',
        'active',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function toData(): BankAccountData
    {
        return new BankAccountData(
            id: (string) $this->id,
            bankNameEn: (string) $this->bank_name_en,
            bankNameAr: (string) $this->bank_name_ar,
            beneficiaryName: (string) $this->beneficiary_name,
            accountNumber: (string) $this->account_number,
            logoDocumentId: $this->logo_document_id ? (string) $this->logo_document_id : null,
            active: (bool) $this->active,
            sortOrder: (int) $this->sort_order,
            createdBy: $this->created_by ? (string) $this->created_by : null,
            createdAt: DateTimeImmutable::createFromInterface($this->created_at),
        );
    }
}
