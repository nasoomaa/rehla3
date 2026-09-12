<?php

declare(strict_types=1);

namespace Rehla\Notifications\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Rehla\Notifications\Data\OutboxEnvelope;
use Rehla\Notifications\Models\OutboxMessage;

final class ClaimOutboxBatch
{
    private const int LEASE_MINUTES = 5;

    private const int MAX_ATTEMPTS = 10;

    /**
     * @return list<OutboxEnvelope>
     */
    public function handle(int $limit, string $workerId, CarbonImmutable $now): array
    {
        return DB::transaction(function () use ($limit, $workerId, $now): array {
            $leaseCutoff = $now->subMinutes(self::LEASE_MINUTES);

            $query = OutboxMessage::whereNull('delivered_at')
                ->where('attempts', '<', self::MAX_ATTEMPTS)
                ->where('available_at', '<=', $now)
                ->where(function ($q) use ($leaseCutoff): void {
                    $q->whereNull('locked_at')
                        ->orWhere('locked_at', '<', $leaseCutoff);
                })
                ->orderBy('available_at', 'asc')
                ->limit($limit);

            if (DB::getDriverName() === 'pgsql') {
                $query->lock('FOR UPDATE SKIP LOCKED');
            } else {
                $query->lockForUpdate();
            }

            $messages = $query->get();

            if ($messages->isEmpty()) {
                return [];
            }

            $ids = $messages->pluck('id');

            OutboxMessage::whereIn('id', $ids)->update([
                'locked_at' => $now,
                'locked_by' => $workerId,
            ]);

            return $messages->map(function (OutboxMessage $msg) use ($workerId, $now): OutboxEnvelope {
                $msg->locked_by = $workerId;
                $msg->locked_at = $now;

                return $msg->toEnvelope();
            })->all();
        });
    }
}
