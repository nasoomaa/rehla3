<?php

declare(strict_types=1);

namespace Rehla\TopUps\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Rehla\TopUps\Data\TopUpData;
use Rehla\TopUps\Enums\TopUpStatus;

final class TopUpRequest extends Model
{
    use HasUuids;

    protected $table = 'topup_requests';

    protected $fillable = [
        'id',
        'account_id',
        'wallet_id',
        'bank_account_id',
        'amount_minor',
        'transaction_reference',
        'normalized_reference',
        'receipt_document_id',
        'status',
        'submitted_at',
        'reviewed_by',
        'decided_at',
        'rejection_reason',
        'credit_ledger_entry_id',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'status' => TopUpStatus::class,
        'submitted_at' => 'immutable_datetime',
        'decided_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<CompanyBankAccount, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'bank_account_id');
    }

    public function toData(): TopUpData
    {
        return new TopUpData(
            id: (string) $this->id,
            accountId: (string) $this->account_id,
            walletId: (string) $this->wallet_id,
            bankAccountId: (string) $this->bank_account_id,
            amountMinor: (int) $this->amount_minor,
            transactionReference: (string) $this->transaction_reference,
            normalizedReference: (string) $this->normalized_reference,
            receiptDocumentId: (string) $this->receipt_document_id,
            status: $this->status instanceof TopUpStatus ? $this->status : TopUpStatus::from((string) $this->status),
            submittedAt: DateTimeImmutable::createFromInterface($this->submitted_at),
            reviewedBy: $this->reviewed_by ? (string) $this->reviewed_by : null,
            decidedAt: $this->decided_at ? DateTimeImmutable::createFromInterface($this->decided_at) : null,
            rejectionReason: $this->rejection_reason ? (string) $this->rejection_reason : null,
            creditLedgerEntryId: $this->credit_ledger_entry_id ? (string) $this->credit_ledger_entry_id : null,
        );
    }
}
