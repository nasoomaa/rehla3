<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Fulfillment\Models\ExecutionDocument;
use Rehla\Fulfillment\Models\ExecutionStatusHistory;
use Rehla\Fulfillment\Models\ServiceExecution;
use Rehla\Purchasing\Contracts\ExecutionCreator;
use Rehla\Purchasing\Data\CreateExecutionData;
use Rehla\Purchasing\Data\ExecutionData;

final class CreateExecution implements ExecutionCreator
{
    public function create(CreateExecutionData $data): ExecutionData
    {
        return DB::transaction(function () use ($data): ExecutionData {
            $now = CarbonImmutable::now();
            $executionId = (string) Str::uuid();

            $execution = ServiceExecution::create([
                'id' => $executionId,
                'order_id' => $data->orderId,
                'account_id' => $data->accountId,
                'traveler_id' => $data->travelerId,
                'service_id' => $data->serviceId,
                'form_version_id' => $data->formVersionId,
                'status' => ExecutionStatus::OrderReceived,
                'last_status_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            ExecutionStatusHistory::create([
                'id' => (string) Str::uuid(),
                'execution_id' => $executionId,
                'from_status' => null,
                'to_status' => ExecutionStatus::OrderReceived->value,
                'actor_type' => 'system',
                'actor_id' => null,
                'reason' => 'Order received',
                'created_at' => $now,
            ]);

            foreach ($data->documentIds as $documentId) {
                ExecutionDocument::create([
                    'id' => (string) Str::uuid(),
                    'execution_id' => $executionId,
                    'document_id' => $documentId,
                    'purpose' => 'submission_document',
                    'attached_at' => $now,
                ]);
            }

            return $execution->toExecutionData();
        });
    }
}
