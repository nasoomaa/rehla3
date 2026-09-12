<?php

declare(strict_types=1);

namespace Rehla\TopUps\Queries;

use Rehla\TopUps\Data\TopUpData;
use Rehla\TopUps\Models\TopUpRequest;

final class ListAllTopUps
{
    /**
     * @return list<TopUpData>
     */
    public function execute(): array
    {
        return TopUpRequest::orderBy('submitted_at', 'desc')
            ->get()
            ->map(fn (TopUpRequest $t): TopUpData => $t->toData())
            ->all();
    }
}
