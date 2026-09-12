<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Notifications\Actions\AppendOutboxMessage;
use Rehla\Notifications\Contracts\NotificationChannel;
use Rehla\Notifications\Data\DeliveryResult;
use Rehla\Notifications\Data\OutboxEnvelope;
use Rehla\Notifications\Data\OutboxMessageData;
use Rehla\Notifications\Jobs\DeliverOutboxMessage;
use Rehla\Notifications\Models\Notification;
use Rehla\Notifications\Models\OutboxMessage;

function pendingOutbox(string $eventName, array $payload = []): string
{
    $userId = (string) Str::uuid();

    return app(AppendOutboxMessage::class)->handle(new OutboxMessageData(
        eventName: $eventName,
        aggregateType: 'order',
        aggregateId: (string) Str::uuid(),
        payload: array_merge(['user_id' => $userId, 'order_ref' => 'ORD-TEST'], $payload),
    ));
}

function claimAs(string $workerId, string $messageId, CarbonImmutable $now): void
{
    OutboxMessage::where('id', $messageId)->update([
        'locked_by' => $workerId,
        'locked_at' => $now,
    ]);
}

function runOutboxWorker(string $workerId): int
{
    return Artisan::call('rehla:outbox-worker', ['worker_id' => $workerId]);
}

function notificationFor(string $messageId)
{
    return Notification::where('outbox_message_id', $messageId)->get();
}

function outbox(string $messageId): ?OutboxMessage
{
    return OutboxMessage::find($messageId);
}

it('recovers a message after a worker dies and projects it once', function (): void {
    $now = CarbonImmutable::now();
    $message = pendingOutbox('order.submitted');
    claimAs('dead-worker', $message, $now);

    // Advance past the 5-minute lease
    test()->travel(6)->minutes();
    runOutboxWorker('replacement-worker');

    expect(notificationFor($message))->toHaveCount(1)
        ->and(outbox($message)->delivered_at)->not->toBeNull();

    runOutboxWorker('replacement-worker');
    expect(notificationFor($message))->toHaveCount(1);
});

it('replays a dead-lettered message with required reason, auditing without mutating payload', function (): void {
    $userId = (string) Str::uuid();
    $originalPayload = ['user_id' => $userId, 'order_ref' => 'ORD-999', 'amount' => 50000];

    $messageId = app(AppendOutboxMessage::class)->handle(new OutboxMessageData(
        eventName: 'order.payment_received',
        aggregateType: 'order',
        aggregateId: (string) Str::uuid(),
        payload: $originalPayload,
    ));

    $message = OutboxMessage::find($messageId);
    $message->update([
        'attempts' => 10,
        'dead_lettered_at' => CarbonImmutable::now(),
        'last_error' => 'Connection timeout to external gateway',
        'locked_by' => 'failed-worker',
        'locked_at' => CarbonImmutable::now(),
    ]);

    // Attempting replay without reason must fail
    $exitWithoutReason = Artisan::call('rehla:outbox-replay', [
        'id' => $messageId,
        '--reason' => '',
    ]);
    expect($exitWithoutReason)->toBe(1);

    // Replay with reason succeeds
    $exitWithReason = Artisan::call('rehla:outbox-replay', [
        'id' => $messageId,
        '--reason' => 'Gateway recovered, retrying manual dispatch',
    ]);
    expect($exitWithReason)->toBe(0);

    $reloaded = OutboxMessage::find($messageId);
    expect($reloaded->dead_lettered_at)->toBeNull()
        ->and($reloaded->locked_at)->toBeNull()
        ->and($reloaded->locked_by)->toBeNull()
        ->and($reloaded->delivered_at)->toBeNull()
        ->and($reloaded->attempts)->toBe(0)
        ->and($reloaded->payload)->toEqualCanonicalizing($originalPayload);

    // Verify audit entry created
    $audit = DB::table('audit_entries')
        ->where('subject_type', 'outbox_message')
        ->where('subject_id', $messageId)
        ->where('action', 'outbox.replayed')
        ->orderBy('occurred_at', 'desc')
        ->first();

    expect($audit)->not->toBeNull();
    $metadata = is_string($audit->metadata) ? json_decode($audit->metadata, true) : (array) $audit->metadata;
    expect($metadata['reason'])->toBe('Gateway recovered, retrying manual dispatch');
});

it('forbids replay if actor lacks notifications.manage ability', function (): void {
    $messageId = app(AppendOutboxMessage::class)->handle(new OutboxMessageData(
        eventName: 'order.payment_received',
        aggregateType: 'order',
        aggregateId: (string) Str::uuid(),
        payload: ['user_id' => (string) Str::uuid()],
    ));

    $unauthorizedActorId = (string) Str::uuid();

    $exit = Artisan::call('rehla:outbox-replay', [
        'id' => $messageId,
        '--reason' => 'Unauthorized retry',
        '--actor' => $unauthorizedActorId,
    ]);

    expect($exit)->toBe(1);

    $msg = OutboxMessage::find($messageId);
    expect($msg->delivered_at)->toBeNull();
});

it('calculates exponential backoff and sets dead_lettered_at after 10 attempts', function (): void {
    $messageId = pendingOutbox('test.event');
    $message = OutboxMessage::find($messageId);

    // Simulate failures with custom failing channel
    $failingChannel = new class implements NotificationChannel
    {
        public function name(): string
        {
            return 'failing_channel';
        }

        public function send(OutboxEnvelope $envelope): DeliveryResult
        {
            return DeliveryResult::failure('failing_channel', 'Service Unavailable');
        }
    };

    // First attempt failure
    $job = new DeliverOutboxMessage($message->toEnvelope(), [$failingChannel]);
    app()->call([$job, 'handle']);

    $reloaded = OutboxMessage::find($messageId);
    expect($reloaded->attempts)->toBe(1)
        ->and($reloaded->last_error)->toContain('Service Unavailable')
        ->and($reloaded->available_at->greaterThan(CarbonImmutable::now()))
        ->and($reloaded->dead_lettered_at)->toBeNull();

    // Fast-forward to 9 attempts and fail once more -> must reach dead_lettered_at
    $reloaded->update(['attempts' => 9]);
    $job10 = new DeliverOutboxMessage($reloaded->toEnvelope(), [$failingChannel]);
    app()->call([$job10, 'handle']);

    $dead = OutboxMessage::find($messageId);
    expect($dead->attempts)->toBe(10)
        ->and($dead->dead_lettered_at)->not->toBeNull();
});
