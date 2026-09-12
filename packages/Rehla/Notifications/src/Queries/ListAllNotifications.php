<?php

declare(strict_types=1);

namespace Rehla\Notifications\Queries;

use Rehla\Notifications\Data\NotificationData;
use Rehla\Notifications\Models\Notification;

final class ListAllNotifications
{
    /**
     * @return list<NotificationData>
     */
    public function execute(int $limit = 100): array
    {
        return Notification::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (Notification $n): NotificationData => $n->toData())
            ->all();
    }
}
