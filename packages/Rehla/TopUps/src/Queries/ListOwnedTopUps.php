<?php

declare(strict_types=1);

namespace Rehla\TopUps\Queries;

use Rehla\TopUps\Data\TopUpData;
use Rehla\TopUps\Models\TopUpRequest;

final class ListOwnedTopUps
{
    /**
     * @return array<int, TopUpData>
     */
    public function execute(string $accountId): array
    {
        return TopUpRequest::where('account_id', $accountId)
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(fn (TopUpRequest $t): TopUpData => $t->toData())
            ->all();
    }
}
