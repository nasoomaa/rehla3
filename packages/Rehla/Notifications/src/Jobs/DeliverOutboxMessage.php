<?php

declare(strict_types=1);

namespace Rehla\Notifications\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Rehla\Notifications\Actions\MarkDelivered;
use Rehla\Notifications\Actions\MarkFailed;
use Rehla\Notifications\Contracts\NotificationChannel;
use Rehla\Notifications\Data\OutboxEnvelope;
use Rehla\Notifications\Listeners\InAppNotificationProjector;
use Throwable;

final class DeliverOutboxMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<NotificationChannel>|null  $channels
     */
    public function __construct(
        public readonly OutboxEnvelope $envelope,
        private readonly ?array $channels = null,
    ) {}

    public function handle(
        MarkDelivered $markDelivered,
        MarkFailed $markFailed,
        InAppNotificationProjector $inAppProjector,
    ): void {
        $channels = $this->channels ?? [$inAppProjector];

        $allSuccessful = true;
        $errors = [];

        foreach ($channels as $channel) {
            try {
                $result = $channel->send($this->envelope);
                if (! $result->successful) {
                    $allSuccessful = false;
                    $errors[] = "[{$channel->name()}] ".($result->error ?? 'Delivery failed');
                }
            } catch (Throwable $e) {
                $allSuccessful = false;
                $errors[] = "[{$channel->name()}] ".$e->getMessage();
            }
        }

        if ($allSuccessful) {
            $markDelivered->handle($this->envelope->id);
        } else {
            $sanitizedError = substr(implode('; ', $errors), 0, 1000);
            $newAttempts = $this->envelope->attempts + 1;
            $backoffMinutes = min(60, (int) (2 ** $newAttempts));
            $availableAt = CarbonImmutable::now()->addMinutes($backoffMinutes);

            $markFailed->handle($this->envelope->id, $sanitizedError, $availableAt);
        }
    }
}
