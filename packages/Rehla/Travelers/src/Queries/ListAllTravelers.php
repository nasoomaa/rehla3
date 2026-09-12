<?php

declare(strict_types=1);

namespace Rehla\Travelers\Queries;

use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Models\Traveler;

final class ListAllTravelers
{
    /**
     * @return list<TravelerData>
     */
    public function execute(): array
    {
        return Traveler::orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Traveler $t): TravelerData => $t->toData())
            ->values()
            ->all();
    }
}
