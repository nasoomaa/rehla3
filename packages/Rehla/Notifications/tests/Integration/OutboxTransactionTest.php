<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Notifications\Data\OutboxMessageData;

it('rolls back outbox with the business transaction', function (): void {
    try {
        DB::transaction(function (): void {
            app(OutboxWriter::class)->append(new OutboxMessageData(
                eventName: 'top_up.approved',
                aggregateType: 'top_up',
                aggregateId: (string) Str::uuid(),
                payload: ['amount_minor' => 500000],
            ));
            throw new RuntimeException('force rollback');
        });
    } catch (RuntimeException) {
    }

    expect(DB::table('outbox_messages')->count())->toBe(0);
});

it('commits outbox record when transaction succeeds with generated deduplication key', function (): void {
    $aggregateId = (string) Str::uuid();

    $id = DB::transaction(function () use ($aggregateId): string {
        return app(OutboxWriter::class)->append(new OutboxMessageData(
            eventName: 'top_up.approved',
            aggregateType: 'top_up',
            aggregateId: $aggregateId,
            payload: ['amount_minor' => 500000],
        ));
    });

    $record = DB::table('outbox_messages')->where('id', $id)->first();
    expect($record)->not->toBeNull()
        ->and($record->event_name)->toBe('top_up.approved')
        ->and($record->aggregate_id)->toBe($aggregateId)
        ->and($record->deduplication_key)->not->toBeNull();
});
