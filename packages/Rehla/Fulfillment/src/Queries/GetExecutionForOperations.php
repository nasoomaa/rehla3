<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Queries;

use Rehla\Fulfillment\Data\ExecutionDetails;
use Rehla\Fulfillment\Exceptions\ExecutionAccessDeniedException;
use Rehla\Fulfillment\Exceptions\ExecutionNotFoundException;
use Rehla\Fulfillment\Models\ServiceExecution;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Enums\AbilityName;

final class GetExecutionForOperations
{
    public function handle(string $executionId, ActorData $actor): ExecutionDetails
    {
        if ($actor->type === 'staff' && ! in_array(AbilityName::ExecutionsManage->value, $actor->abilities, true)) {
            throw ExecutionAccessDeniedException::manageDenied();
        }

        /** @var ServiceExecution|null $execution */
        $execution = ServiceExecution::query()
            ->where('id', $executionId)
            ->first();

        if ($execution === null) {
            throw ExecutionNotFoundException::forId($executionId);
        }

        return $execution->toExecutionDetails();
    }
}
