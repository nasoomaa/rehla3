<?php

declare(strict_types=1);

namespace Rehla\Notifications\Listeners;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Rehla\Notifications\Contracts\NotificationChannel;
use Rehla\Notifications\Data\DeliveryResult;
use Rehla\Notifications\Data\OutboxEnvelope;
use Rehla\Notifications\Models\Notification;
use Throwable;

final class InAppNotificationProjector implements NotificationChannel
{
    public function name(): string
    {
        return 'in_app';
    }

    public function send(OutboxEnvelope $envelope): DeliveryResult
    {
        $userId = $envelope->payload['user_id'] ?? $envelope->payload['customer_id'] ?? null;

        if ($userId === null || ! is_string($userId) || trim($userId) === '') {
            return DeliveryResult::success($this->name());
        }

        try {
            Notification::firstOrCreate(
                [
                    'user_id' => $userId,
                    'outbox_message_id' => $envelope->id,
                    'channel' => $this->name(),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'type' => $envelope->eventName,
                    'payload' => $envelope->payload,
                ]
            );

            return DeliveryResult::success($this->name());
        } catch (UniqueConstraintViolationException) {
            return DeliveryResult::success($this->name());
        } catch (Throwable $e) {
            return DeliveryResult::failure($this->name(), $e->getMessage());
        }
    }
}
