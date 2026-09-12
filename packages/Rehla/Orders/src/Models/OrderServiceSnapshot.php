<?php

declare(strict_types=1);

namespace Rehla\Orders\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Rehla\Orders\Data\ServiceSnapshotData;

final class OrderServiceSnapshot extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'order_service_snapshots';

    protected $fillable = [
        'id',
        'order_id',
        'name_en',
        'name_ar',
        'descriptions',
        'requirements',
        'expected_duration',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'descriptions' => 'array',
        'requirements' => 'array',
        'expected_duration' => 'array',
        'notes' => 'array',
        'created_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function toData(): ServiceSnapshotData
    {
        return new ServiceSnapshotData(
            nameEn: (string) $this->name_en,
            nameAr: (string) $this->name_ar,
            descriptions: (array) ($this->descriptions ?? []),
            requirements: (array) ($this->requirements ?? []),
            expectedDuration: (array) ($this->expected_duration ?? []),
            notes: (array) ($this->notes ?? []),
        );
    }
}
