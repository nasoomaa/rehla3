<?php

declare(strict_types=1);

namespace Rehla\Catalog\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Rehla\Catalog\Data\ServicePricePoint;

final class ServicePriceHistory extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'service_price_history';

    protected $fillable = [
        'id',
        'service_id',
        'price_minor',
        'currency',
        'version',
        'changed_by',
        'effective_at',
        'created_at',
    ];

    protected $casts = [
        'price_minor' => 'integer',
        'version' => 'integer',
        'effective_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function toData(): ServicePricePoint
    {
        return new ServicePricePoint(
            id: (string) $this->id,
            serviceId: (string) $this->service_id,
            priceMinor: (int) $this->price_minor,
            currency: (string) $this->currency,
            version: (int) $this->version,
            changedBy: $this->changed_by ? (string) $this->changed_by : null,
            effectiveAt: DateTimeImmutable::createFromInterface($this->effective_at),
            createdAt: DateTimeImmutable::createFromInterface($this->created_at),
        );
    }
}
