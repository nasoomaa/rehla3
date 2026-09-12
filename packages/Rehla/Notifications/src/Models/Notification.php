<?php

declare(strict_types=1);

namespace Rehla\Notifications\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Rehla\Notifications\Data\NotificationData;

class Notification extends Model
{
    use HasUuids;

    protected $table = 'notifications';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'outbox_message_id',
        'channel',
        'type',
        'payload',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function toData(): NotificationData
    {
        return new NotificationData(
            id: (string) $this->id,
            userId: (string) $this->user_id,
            type: (string) $this->type,
            channel: (string) ($this->channel ?? 'in_app'),
            payload: (array) $this->payload,
            readAt: $this->read_at !== null ? CarbonImmutable::instance($this->read_at) : null,
            createdAt: $this->created_at !== null ? CarbonImmutable::instance($this->created_at) : null,
        );
    }
}
