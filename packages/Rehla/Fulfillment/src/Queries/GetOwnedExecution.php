<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Queries;

use Rehla\Fulfillment\Data\ExecutionDetails;
use Rehla\Fulfillment\Exceptions\ExecutionNotFoundException;
use Rehla\Fulfillment\Models\ServiceExecution;

final class GetOwnedExecution
{
    public function handle(string $accountId, string $executionId): ExecutionDetails
    {
        /** @var ServiceExecution|null $execution */
        $execution = ServiceExecution::query()
            ->where('id', $executionId)
            ->where('account_id', $accountId)
            ->first();

        if ($execution === null) {
            throw ExecutionNotFoundException::forId($executionId);
        }

        return $execution->toExecutionDetails();
    }

    public function handleByOrderId(string $accountId, string $orderId): ?ExecutionDetails
    {
        /** @var ServiceExecution|null $execution */
        $execution = ServiceExecution::query()
            ->where('order_id', $orderId)
            ->where('account_id', $accountId)
            ->first();

        return $execution?->toExecutionDetails();
    }
}
