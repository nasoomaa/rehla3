<?php

declare(strict_types=1);

namespace Rehla\Orders\Queries;

use Rehla\Orders\Data\OrderSummaryData;
use Rehla\Orders\Models\Order;

final class ListAllOrders
{
    /**
     * @return list<OrderSummaryData>
     */
    public function execute(): array
    {
        return Order::orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Order $order): OrderSummaryData => $order->toSummaryData())
            ->values()
            ->all();
    }
}
