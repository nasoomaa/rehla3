<?php

declare(strict_types=1);

namespace Rehla\Notifications\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Rehla\Notifications\Actions\ClaimOutboxBatch;
use Rehla\Notifications\Jobs\DeliverOutboxMessage;

final class RunOutboxWorker extends Command
{
    protected $signature = 'rehla:outbox-worker {worker_id?}';

    protected $description = 'Claim and deliver pending outbox messages';

    public function handle(ClaimOutboxBatch $claimer): int
    {
        $workerId = (string) ($this->argument('worker_id') ?: 'worker-'.Str::random(8));
        $now = CarbonImmutable::now();

        $envelopes = $claimer->handle(100, $workerId, $now);

        foreach ($envelopes as $envelope) {
            DeliverOutboxMessage::dispatchSync($envelope);
        }

        $count = count($envelopes);
        $this->info("Processed {$count} outbox message(s) for worker {$workerId}.");

        return 0;
    }
}
