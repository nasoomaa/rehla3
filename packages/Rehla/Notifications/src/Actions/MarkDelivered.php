<?php

declare(strict_types=1);

namespace Rehla\Notifications\Actions;

use Carbon\CarbonImmutable;
use Rehla\Notifications\Models\OutboxMessage;

final class MarkDelivered
{
    public function handle(string $messageId, ?CarbonImmutable $deliveredAt = null): void
    {
        $delivered = $deliveredAt ?? CarbonImmutable::now();

        OutboxMessage::where('id', $messageId)->update([
            'delivered_at' => $delivered,
            'locked_at' => null,
            'locked_by' => null,
        ]);
    }
}
