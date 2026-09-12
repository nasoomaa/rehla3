<?php

declare(strict_types=1);

namespace Rehla\Notifications\Actions;

use Illuminate\Support\Facades\DB;
use Rehla\Notifications\Models\OutboxMessage;

final class MarkFailed
{
    public function handle(string $messageId, string $error): void
    {
        OutboxMessage::where('id', $messageId)->update([
            'attempts' => DB::raw('attempts + 1'),
            'last_error' => $error,
            'locked_at' => null,
            'locked_by' => null,
        ]);
    }
}
