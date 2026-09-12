<?php

declare(strict_types=1);

namespace Rehla\Notifications\Actions;

use Carbon\CarbonImmutable;
use Rehla\Notifications\Models\Notification;

final class MarkNotificationRead
{
    public function handle(string $notificationId, string $userId): void
    {
        Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->update([
                'read_at' => CarbonImmutable::now(),
            ]);
    }
}
