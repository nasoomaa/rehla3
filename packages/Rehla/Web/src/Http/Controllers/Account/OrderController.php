<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Controllers\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Rehla\Orders\Exceptions\OrderNotFoundException;
use Rehla\Orders\Queries\GetOwnedOrder;
use Rehla\Orders\Queries\ListOwnedOrders;

final class OrderController
{
    public function index(ListOwnedOrders $listOrders): View
    {
        $orders = $listOrders->handle((string) Auth::id());

        return view('rehla-web::account.orders.index', [
            'orders' => $orders,
        ]);
    }

    public function show(string $id, GetOwnedOrder $getOrder): View
    {
        try {
            $order = $getOrder->handle((string) Auth::id(), $id);

            return view('rehla-web::account.orders.show', [
                'order' => $order,
            ]);
        } catch (OrderNotFoundException) {
            abort(404);
        }
    }
}
