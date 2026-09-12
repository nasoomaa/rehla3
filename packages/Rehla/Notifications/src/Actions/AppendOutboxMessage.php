<?php

declare(strict_types=1);

namespace Rehla\Notifications\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Notifications\Data\OutboxMessageData;
use Rehla\Notifications\Models\OutboxMessage;

final class AppendOutboxMessage implements OutboxWriter
{
    public function append(OutboxMessageData $data): string
    {
        return $this->handle($data);
    }

    public function handle(OutboxMessageData $data): string
    {
        $id = (string) Str::uuid();
        $dedupKey = $data->deduplicationKey ?? "{$data->aggregateType}:{$data->aggregateId}:{$data->eventName}:{$id}";

        OutboxMessage::create([
            'id' => $id,
            'event_name' => $data->eventName,
            'aggregate_type' => $data->aggregateType,
            'aggregate_id' => $data->aggregateId,
            'payload_version' => $data->payloadVersion,
            'payload' => $data->payload,
            'deduplication_key' => $dedupKey,
            'available_at' => CarbonImmutable::now(),
            'created_at' => CarbonImmutable::now(),
        ]);

        return $id;
    }
}
