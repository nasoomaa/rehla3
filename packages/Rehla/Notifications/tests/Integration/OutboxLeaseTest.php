<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Rehla\Notifications\Actions\AppendOutboxMessage;
use Rehla\Notifications\Actions\ClaimOutboxBatch;
use Rehla\Notifications\Actions\MarkDelivered;
use Rehla\Notifications\Actions\MarkFailed;
use Rehla\Notifications\Data\OutboxMessageData;
use Rehla\Notifications\Models\OutboxMessage;

it('claims messages atomically with 5 minute lease and skips locked or claimed records', function (): void {
    $now = CarbonImmutable::now();

    // 1. Append message
    $id = app(AppendOutboxMessage::class)->handle(new OutboxMessageData(
        eventName: 'order.created',
        aggregateType: 'order',
        aggregateId: (string) Str::uuid(),
        payload: ['order_ref' => 'ORD-123'],
    ));

    // 2. Worker 1 claims batch
    $batch1 = app(ClaimOutboxBatch::class)->handle(10, 'worker-1', $now);
    expect($batch1)->toHaveCount(1)
        ->and($batch1[0]->id)->toBe($id)
        ->and($batch1[0]->lockedBy)->toBe('worker-1');

    // 3. Worker 2 immediately attempts to claim — must be empty (active lease)
    $batch2 = app(ClaimOutboxBatch::class)->handle(10, 'worker-2', $now);
    expect($batch2)->toBeEmpty();

    // 4. Advance time past 5 minute lease (6 minutes later) -> Worker 2 can reclaim expired lease
    $future = $now->addMinutes(6);
    $batch3 = app(ClaimOutboxBatch::class)->handle(10, 'worker-2', $future);
    expect($batch3)->toHaveCount(1)
        ->and($batch3[0]->id)->toBe($id)
        ->and($batch3[0]->lockedBy)->toBe('worker-2');
});

it('marks delivered messages and prevents further claiming', function (): void {
    $now = CarbonImmutable::now();
    $id = app(AppendOutboxMessage::class)->handle(new OutboxMessageData(
        eventName: 'wallet.credited',
        aggregateType: 'wallet',
        aggregateId: (string) Str::uuid(),
        payload: ['amount' => 100],
    ));

    $batch = app(ClaimOutboxBatch::class)->handle(10, 'worker-1', $now);
    expect($batch)->toHaveCount(1);

    app(MarkDelivered::class)->handle($id, $now);

    $msg = OutboxMessage::find($id);
    expect($msg->delivered_at)->not->toBeNull();

    // Never claimed again
    expect(app(ClaimOutboxBatch::class)->handle(10, 'worker-1', $now->addMinutes(10)))->toBeEmpty();
});

it('increments attempts on failure and sends to dead-letter state after 10 attempts without deleting', function (): void {
    $now = CarbonImmutable::now();
    $id = app(AppendOutboxMessage::class)->handle(new OutboxMessageData(
        eventName: 'document.scanned',
        aggregateType: 'document',
        aggregateId: (string) Str::uuid(),
        payload: ['clean' => true],
    ));

    for ($i = 1; $i <= 10; $i++) {
        $batch = app(ClaimOutboxBatch::class)->handle(10, 'worker-1', $now->addMinutes($i * 6));
        expect($batch)->toHaveCount(1);
        app(MarkFailed::class)->handle($id, "Attempt {$i} timeout");
    }

    $msg = OutboxMessage::find($id);
    expect($msg)->not->toBeNull()
        ->and($msg->attempts)->toBe(10)
        ->and($msg->last_error)->toContain('Attempt 10 timeout');

    // After 10 attempts, dead letter -> cannot be claimed anymore
    $batchDead = app(ClaimOutboxBatch::class)->handle(10, 'worker-1', $now->addHours(10));
    expect($batchDead)->toBeEmpty();
});
