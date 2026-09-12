<?php

declare(strict_types=1);

namespace Rehla\Notifications\Actions;

use Illuminate\Support\Str;
use Rehla\Notifications\Models\Notification;

final class CreateInAppNotification
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $userId, string $type, array $payload): Notification
    {
        return Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'type' => $type,
            'payload' => $payload,
        ]);
    }
}
