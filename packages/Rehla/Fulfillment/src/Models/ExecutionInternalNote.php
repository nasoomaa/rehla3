<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ExecutionInternalNote extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'execution_internal_notes';

    protected $fillable = [
        'id',
        'execution_id',
        'staff_id',
        'body',
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
