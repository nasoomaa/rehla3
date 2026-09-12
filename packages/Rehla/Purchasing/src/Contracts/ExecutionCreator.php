<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Contracts;

use Rehla\Purchasing\Data\CreateExecutionData;
use Rehla\Purchasing\Data\ExecutionData;

interface ExecutionCreator
{
    public function create(CreateExecutionData $data): ExecutionData;
}
