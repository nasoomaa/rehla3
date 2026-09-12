<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rehla\Fulfillment\Data\CustomerActionRequestData;
use Rehla\Fulfillment\Data\ExecutionDetails;
use Rehla\Fulfillment\Data\StatusHistoryEntryData;
use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Purchasing\Data\ExecutionData;

final class ServiceExecution extends Model
{
    use HasUuids;

    protected $table = 'service_executions';

    protected $fillable = [
        'id',
        'order_id',
        'account_id',
        'traveler_id',
        'service_id',
        'form_version_id',
        'status',
        'last_status_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'status' => ExecutionStatus::class,
        'last_status_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * @return HasMany<ExecutionStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(ExecutionStatusHistory::class, 'execution_id')->orderBy('created_at');
    }

    /**
     * @return HasMany<ExecutionInternalNote, $this>
     */
    public function internalNotes(): HasMany
    {
        return $this->hasMany(ExecutionInternalNote::class, 'execution_id')->orderBy('created_at');
    }

    /**
     * @return HasMany<CustomerActionRequest, $this>
     */
    public function actionRequests(): HasMany
    {
        return $this->hasMany(CustomerActionRequest::class, 'execution_id')->orderBy('created_at');
    }

    /**
     * @return HasMany<ExecutionDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ExecutionDocument::class, 'execution_id');
    }

    public function toExecutionData(): ExecutionData
    {
        return new ExecutionData(
            id: (string) $this->id,
            orderId: (string) $this->order_id,
            accountId: (string) $this->account_id,
            travelerId: (string) $this->traveler_id,
            serviceId: (string) $this->service_id,
            formVersionId: (string) $this->form_version_id,
            status: $this->status instanceof ExecutionStatus ? $this->status->value : (string) $this->status,
            createdAt: DateTimeImmutable::createFromInterface($this->created_at),
            lastStatusAt: $this->last_status_at ? DateTimeImmutable::createFromInterface($this->last_status_at) : null,
        );
    }

    public function toExecutionDetails(): ExecutionDetails
    {
        $this->loadMissing(['statusHistory', 'actionRequests', 'documents']);

        $history = $this->statusHistory->map(fn (ExecutionStatusHistory $h) => new StatusHistoryEntryData(
            id: (string) $h->id,
            fromStatus: $h->from_status ? (string) $h->from_status : null,
            toStatus: (string) $h->to_status,
            actorType: (string) $h->actor_type,
            actorId: $h->actor_id ? (string) $h->actor_id : null,
            reason: $h->reason ? (string) $h->reason : null,
            createdAt: DateTimeImmutable::createFromInterface($h->created_at),
        ))->values()->all();

        $actionRequests = $this->actionRequests->map(fn (CustomerActionRequest $ar) => new CustomerActionRequestData(
            id: (string) $ar->id,
            executionId: (string) $ar->execution_id,
            descriptionEn: (string) $ar->description_en,
            descriptionAr: (string) $ar->description_ar,
            requiredDocumentPurpose: $ar->required_document_purpose ? (string) $ar->required_document_purpose : null,
            status: (string) $ar->status,
            dueAt: $ar->due_at ? DateTimeImmutable::createFromInterface($ar->due_at) : null,
            createdAt: DateTimeImmutable::createFromInterface($ar->created_at),
            resolvedAt: $ar->resolved_at ? DateTimeImmutable::createFromInterface($ar->resolved_at) : null,
        ))->values()->all();

        $documentIds = $this->documents->pluck('document_id')->map(fn ($id) => (string) $id)->values()->all();

        return new ExecutionDetails(
            id: (string) $this->id,
            orderId: (string) $this->order_id,
            accountId: (string) $this->account_id,
            travelerId: (string) $this->traveler_id,
            serviceId: (string) $this->service_id,
            formVersionId: (string) $this->form_version_id,
            status: $this->status instanceof ExecutionStatus ? $this->status->value : (string) $this->status,
            createdAt: DateTimeImmutable::createFromInterface($this->created_at),
            lastStatusAt: DateTimeImmutable::createFromInterface($this->last_status_at),
            history: $history,
            actionRequests: $actionRequests,
            attachedDocumentIds: $documentIds,
        );
    }
}
