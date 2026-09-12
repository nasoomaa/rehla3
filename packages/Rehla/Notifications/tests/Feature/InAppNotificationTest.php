<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rehla\Notifications\Actions\AppendOutboxMessage;
use Rehla\Notifications\Data\OutboxEnvelope;
use Rehla\Notifications\Data\OutboxMessageData;
use Rehla\Notifications\Listeners\InAppNotificationProjector;
use Rehla\Notifications\Models\Notification;
use Rehla\Notifications\Models\OutboxMessage;

it('projects an outbox message into in-app notification idempotently', function (): void {
    $userId = (string) Str::uuid();
    $messageId = app(AppendOutboxMessage::class)->handle(new OutboxMessageData(
        eventName: 'wallet.credited',
        aggregateType: 'wallet',
        aggregateId: (string) Str::uuid(),
        payload: [
            'user_id' => $userId,
            'amount_minor' => 150000,
            'currency' => 'SDG',
        ],
    ));

    $message = OutboxMessage::find($messageId);
    $envelope = $message->toEnvelope();

    $projector = app(InAppNotificationProjector::class);

    // First projection
    $result1 = $projector->send($envelope);
    expect($result1->successful)->toBeTrue()
        ->and($result1->channel)->toBe('in_app');

    $notifications = Notification::where('user_id', $userId)
        ->where('outbox_message_id', $messageId)
        ->get();

    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->type)->toBe('wallet.credited')
        ->and($notifications->first()->channel)->toBe('in_app')
        ->and($notifications->first()->payload['amount_minor'])->toBe(150000);

    // Duplicate projection attempt must be idempotent
    $result2 = $projector->send($envelope);
    expect($result2->successful)->toBeTrue();

    $count = Notification::where('user_id', $userId)
        ->where('outbox_message_id', $messageId)
        ->count();

    expect($count)->toBe(1);
});

it('gracefully skips events without user recipient', function (): void {
    $envelope = new OutboxEnvelope(
        id: (string) Str::uuid(),
        eventName: 'system.maintenance_scheduled',
        aggregateType: 'system',
        aggregateId: (string) Str::uuid(),
        payloadVersion: 1,
        payload: ['window' => '2026-09-15 02:00:00 UTC'],
        deduplicationKey: 'system:maint:1',
        attempts: 0,
    );

    $projector = app(InAppNotificationProjector::class);
    $result = $projector->send($envelope);

    expect($result->successful)->toBeTrue()
        ->and($result->channel)->toBe('in_app');

    expect(Notification::where('outbox_message_id', $envelope->id)->count())->toBe(0);
});
