<?php

declare(strict_types=1);

namespace Rehla\Wallet\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Rehla\Wallet\Data\WalletEntryData;
use Rehla\Wallet\Enums\LedgerEntryType;

final class LedgerEntry extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'ledger_entries';

    protected $fillable = [
        'id',
        'wallet_id',
        'type',
        'amount_minor',
        'balance_after_minor',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'reverses_entry_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'type' => LedgerEntryType::class,
        'amount_minor' => 'integer',
        'balance_after_minor' => 'integer',
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function toData(): WalletEntryData
    {
        return new WalletEntryData(
            id: (string) $this->id,
            walletId: (string) $this->wallet_id,
            type: $this->type instanceof LedgerEntryType ? $this->type : LedgerEntryType::from((string) $this->type),
            amountMinor: (int) $this->amount_minor,
            balanceAfterMinor: (int) $this->balance_after_minor,
            referenceType: (string) $this->reference_type,
            referenceId: (string) $this->reference_id,
            idempotencyKey: (string) $this->idempotency_key,
            reversesEntryId: $this->reverses_entry_id ? (string) $this->reverses_entry_id : null,
            metadata: (array) ($this->metadata ?? []),
            createdAt: $this->created_at ? DateTimeImmutable::createFromInterface($this->created_at) : new DateTimeImmutable,
        );
    }
}
