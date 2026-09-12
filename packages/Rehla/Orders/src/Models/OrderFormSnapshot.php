<?php

declare(strict_types=1);

namespace Rehla\Orders\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Rehla\Orders\Data\FormSnapshotData;

final class OrderFormSnapshot extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'order_form_snapshots';

    protected $fillable = [
        'id',
        'order_id',
        'form_version_id',
        'form_version',
        'form_checksum',
        'schema',
        'answers',
        'created_at',
    ];

    protected $casts = [
        'form_version' => 'integer',
        'schema' => 'array',
        'answers' => 'array',
        'created_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function toData(): FormSnapshotData
    {
        return new FormSnapshotData(
            formVersionId: (string) $this->form_version_id,
            formVersion: (int) $this->form_version,
            formChecksum: (string) $this->form_checksum,
            schema: (array) ($this->schema ?? []),
            answers: (array) ($this->answers ?? []),
        );
    }
}
