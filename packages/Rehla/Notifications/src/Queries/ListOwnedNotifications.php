<?php

declare(strict_types=1);

namespace Rehla\Notifications\Queries;

use Rehla\Notifications\Data\NotificationData;
use Rehla\Notifications\Models\Notification;

final class ListOwnedNotifications
{
    /**
     * @return list<NotificationData>
     */
    public function execute(string $userId, int $limit = 50): array
    {
        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (Notification $n): NotificationData => $n->toData())
            ->all();
    }
}
