<?php

declare(strict_types=1);

namespace Rehla\Travelers\Services;

use Rehla\Travelers\Contracts\ResolvesTravelerSnapshots;
use Rehla\Travelers\Data\TravelerSnapshot;
use Rehla\Travelers\Exceptions\TravelerNotFound;
use Rehla\Travelers\Models\Traveler;

final class TravelerSnapshotResolver implements ResolvesTravelerSnapshots
{
    /**
     * @param  list<string>  $travelerIds
     * @return list<TravelerSnapshot>
     */
    public function resolve(array $travelerIds, string $accountId): array
    {
        if (empty($travelerIds)) {
            return [];
        }

        $travelers = Traveler::whereIn('id', $travelerIds)
            ->where('owner_id', $accountId)
            ->get();

        if ($travelers->count() !== count($travelerIds)) {
            throw new TravelerNotFound("One or more travelers not found for account {$accountId}");
        }

        return $travelers->map(fn (Traveler $t): TravelerSnapshot => $t->toSnapshot())
            ->values()
            ->all();
    }
}
