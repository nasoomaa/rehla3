<?php

declare(strict_types=1);

namespace Rehla\Travelers\Contracts;

use Rehla\Travelers\Data\TravelerSnapshot;

interface ResolvesTravelerSnapshots
{
    /**
     * @param  list<string>  $travelerIds
     * @return list<TravelerSnapshot>
     */
    public function resolve(array $travelerIds, string $accountId): array;
}
