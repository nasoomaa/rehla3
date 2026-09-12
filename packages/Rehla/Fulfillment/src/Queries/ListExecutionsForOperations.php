<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Queries;

use Rehla\Fulfillment\Data\ExecutionDetails;
use Rehla\Fulfillment\Models\ServiceExecution;

final class ListExecutionsForOperations
{
    /**
     * @return list<ExecutionDetails>
     */
    public function execute(): array
    {
        return ServiceExecution::orderBy('created_at', 'desc')
            ->get()
            ->map(fn (ServiceExecution $e): ExecutionDetails => $e->toExecutionDetails())
            ->all();
    }
}
