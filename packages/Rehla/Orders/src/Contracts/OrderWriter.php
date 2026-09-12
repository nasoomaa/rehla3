<?php

declare(strict_types=1);

namespace Rehla\Orders\Contracts;

use Rehla\Orders\Data\CreatePaidOrderData;
use Rehla\Orders\Data\PaidOrderData;

interface OrderWriter
{
    public function createPaid(CreatePaidOrderData $data): PaidOrderData;
}
