<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Rehla\Purchasing\Enums\PurchaseAttemptStatus;

final class PurchaseAttempt extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'purchase_attempts';

    protected $fillable = [
        'id',
        'account_id',
        'idempotency_key',
        'request_fingerprint',
        'status',
        'order_id',
        'response_status',
        'response_body',
        'created_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => PurchaseAttemptStatus::class,
        'response_status' => 'integer',
        'response_body' => 'array',
        'created_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
    ];

    public function isCompleted(): bool
    {
        return $this->status === PurchaseAttemptStatus::Completed;
    }

    public function isInProgress(): bool
    {
        return $this->status === PurchaseAttemptStatus::InProgress;
    }
}
