<?php

declare(strict_types=1);

namespace Rehla\Notifications\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Rehla\Notifications\Data\OutboxEnvelope;

class OutboxMessage extends Model
{
    use HasUuids;

    protected $table = 'outbox_messages';

    public $timestamps = false;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'event_name',
        'aggregate_type',
        'aggregate_id',
        'payload_version',
        'payload',
        'deduplication_key',
        'available_at',
        'locked_at',
        'locked_by',
        'attempts',
        'delivered_at',
        'dead_lettered_at',
        'last_error',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'payload_version' => 'integer',
            'attempts' => 'integer',
            'available_at' => 'datetime',
            'locked_at' => 'datetime',
            'delivered_at' => 'datetime',
            'dead_lettered_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function toEnvelope(): OutboxEnvelope
    {
        return new OutboxEnvelope(
            id: (string) $this->id,
            eventName: (string) $this->event_name,
            aggregateType: (string) $this->aggregate_type,
            aggregateId: (string) $this->aggregate_id,
            payloadVersion: (int) $this->payload_version,
            payload: (array) $this->payload,
            deduplicationKey: (string) $this->deduplication_key,
            attempts: (int) $this->attempts,
            lockedBy: $this->locked_by !== null ? (string) $this->locked_by : null,
            lockedAt: $this->locked_at !== null ? CarbonImmutable::instance($this->locked_at) : null,
        );
    }
}
