<?php

declare(strict_types=1);

namespace Rehla\Travelers\Queries;

use Rehla\Travelers\Data\TravelerSnapshot;
use Rehla\Travelers\Exceptions\TravelerNotFound;
use Rehla\Travelers\Models\Traveler;

final class GetOwnedTravelerSnapshot
{
    public function handle(string $accountId, string $travelerId): TravelerSnapshot
    {
        /** @var Traveler|null $traveler */
        $traveler = Traveler::where('id', $travelerId)
            ->where('owner_id', $accountId)
            ->first();

        if ($traveler === null) {
            throw new TravelerNotFound("Traveler not found for account {$accountId}");
        }

        return $traveler->toSnapshot();
    }
}
