<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ExecutionDocument extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'execution_documents';

    protected $fillable = [
        'id',
        'execution_id',
        'document_id',
        'purpose',
        'attached_at',
    ];

    protected $casts = [
        'attached_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<ServiceExecution, $this>
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(ServiceExecution::class, 'execution_id');
    }
}
