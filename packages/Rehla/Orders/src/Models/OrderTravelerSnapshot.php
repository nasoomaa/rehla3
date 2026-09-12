<?php

declare(strict_types=1);

namespace Rehla\Orders\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Rehla\Orders\Data\TravelerSnapshotData;

final class OrderTravelerSnapshot extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'order_traveler_snapshots';

    protected $fillable = [
        'id',
        'order_id',
        'full_name',
        'date_of_birth',
        'gender',
        'passport_number',
        'passport_issued_at',
        'passport_expires_at',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function toData(): TravelerSnapshotData
    {
        return new TravelerSnapshotData(
            fullName: (string) $this->full_name,
            dateOfBirth: (string) $this->date_of_birth,
            gender: (string) $this->gender,
            passportNumber: (string) $this->passport_number,
            passportIssuedAt: $this->passport_issued_at ? (string) $this->passport_issued_at : null,
            passportExpiresAt: $this->passport_expires_at ? (string) $this->passport_expires_at : null,
        );
    }
}
