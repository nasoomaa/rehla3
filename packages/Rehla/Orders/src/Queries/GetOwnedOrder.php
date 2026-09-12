<?php

declare(strict_types=1);

namespace Rehla\Orders\Queries;

use Rehla\Orders\Data\PaidOrderData;
use Rehla\Orders\Exceptions\OrderNotFoundException;
use Rehla\Orders\Models\Order;

final class GetOwnedOrder
{
    public function handle(string $accountId, string $orderId): PaidOrderData
    {
        /** @var Order|null $order */
        $order = Order::query()
            ->where('id', $orderId)
            ->where('account_id', $accountId)
            ->first();

        if ($order === null) {
            throw OrderNotFoundException::forId($orderId);
        }

        return $order->toPaidOrderData();
    }
}
