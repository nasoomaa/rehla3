<?php

declare(strict_types=1);

namespace Rehla\TopUps\Queries;

use Rehla\TopUps\Data\TopUpData;
use Rehla\TopUps\Models\TopUpRequest;

final class GetTopUpForReview
{
    public function execute(string $topUpId): ?TopUpData
    {
        /** @var TopUpRequest|null $request */
        $request = TopUpRequest::with('bankAccount')->find($topUpId);

        return $request?->toData();
    }
}
