<?php

declare(strict_types=1);

namespace Rehla\Travelers\Queries;

use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Models\Traveler;

final class ListOwnedTravelers
{
    /**
     * @return list<TravelerData>
     */
    public function handle(string $ownerId): array
    {
        return Traveler::where('owner_id', $ownerId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Traveler $t): TravelerData => $t->toData())
            ->values()
            ->all();
    }
}
