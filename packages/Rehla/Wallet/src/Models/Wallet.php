<?php

declare(strict_types=1);

namespace Rehla\Wallet\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rehla\Wallet\Data\WalletBalance;
use Rehla\Wallet\Data\WalletData;

final class Wallet extends Model
{
    use HasUuids;

    protected $table = 'wallets';

    protected $fillable = [
        'id',
        'account_id',
        'currency',
        'balance_minor',
        'lock_version',
    ];

    protected $casts = [
        'balance_minor' => 'integer',
        'lock_version' => 'integer',
    ];

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'wallet_id')->orderBy('created_at', 'asc');
    }

    public function toData(): WalletData
    {
        return new WalletData(
            id: (string) $this->id,
            accountId: (string) $this->account_id,
            currency: (string) $this->currency,
            balanceMinor: (int) $this->balance_minor,
            lockVersion: (int) $this->lock_version,
            createdAt: DateTimeImmutable::createFromInterface($this->created_at),
            updatedAt: DateTimeImmutable::createFromInterface($this->updated_at),
        );
    }

    public function toBalance(): WalletBalance
    {
        return new WalletBalance(
            walletId: (string) $this->id,
            minor: (int) $this->balance_minor,
            currency: (string) $this->currency,
        );
    }
}
