<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ExecutionStatusHistory extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'execution_status_history';

    protected $fillable = [
        'id',
        'execution_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<ServiceExecution, $this>
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(ServiceExecution::class, 'execution_id');
    }
}
