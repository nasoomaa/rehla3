<?php

declare(strict_types=1);

namespace Rehla\Notifications\Actions;

use Carbon\CarbonImmutable;
use Rehla\Notifications\Models\OutboxMessage;

final class MarkFailed
{
    private const int MAX_ATTEMPTS = 10;

    public function handle(string $messageId, string $error, ?CarbonImmutable $availableAt = null): void
    {
        $message = OutboxMessage::find($messageId);
        if ($message === null) {
            return;
        }

        $newAttempts = $message->attempts + 1;
        $updates = [
            'attempts' => $newAttempts,
            'last_error' => $error,
            'locked_at' => null,
            'locked_by' => null,
        ];

        if ($newAttempts >= self::MAX_ATTEMPTS) {
            $updates['dead_lettered_at'] = CarbonImmutable::now();
        }

        if ($availableAt !== null) {
            $updates['available_at'] = $availableAt;
        }

        $message->update($updates);
    }
}
