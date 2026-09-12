<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CustomerActionRequest extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'customer_action_requests';

    protected $fillable = [
        'id',
        'execution_id',
        'requested_by',
        'description_en',
        'description_ar',
        'required_document_purpose',
        'status',
        'due_at',
        'created_at',
        'resolved_at',
    ];

    protected $casts = [
        'due_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'resolved_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<ServiceExecution, $this>
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(ServiceExecution::class, 'execution_id');
    }
}
