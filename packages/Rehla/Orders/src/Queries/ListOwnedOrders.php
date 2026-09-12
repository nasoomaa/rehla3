<?php

declare(strict_types=1);

namespace Rehla\Orders\Queries;

use Rehla\Orders\Data\OrderSummaryData;
use Rehla\Orders\Models\Order;

final class ListOwnedOrders
{
    /**
     * @return list<OrderSummaryData>
     */
    public function handle(string $accountId): array
    {
        return Order::query()
            ->where('account_id', $accountId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Order $order) => $order->toSummaryData())
            ->values()
            ->all();
    }
}
