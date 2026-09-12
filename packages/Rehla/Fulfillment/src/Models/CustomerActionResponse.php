<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Rehla\Fulfillment\Data\CustomerActionResponseData;

final class CustomerActionResponse extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'customer_action_responses';

    protected $fillable = [
        'id',
        'action_request_id',
        'account_id',
        'message',
        'document_id',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<CustomerActionRequest, $this>
     */
    public function actionRequest(): BelongsTo
    {
        return $this->belongsTo(CustomerActionRequest::class, 'action_request_id');
    }

    public function toData(): CustomerActionResponseData
    {
        return new CustomerActionResponseData(
            id: (string) $this->id,
            actionRequestId: (string) $this->action_request_id,
            accountId: (string) $this->account_id,
            message: $this->message ? (string) $this->message : null,
            documentId: $this->document_id ? (string) $this->document_id : null,
            submittedAt: DateTimeImmutable::createFromInterface($this->submitted_at),
        );
    }
}
