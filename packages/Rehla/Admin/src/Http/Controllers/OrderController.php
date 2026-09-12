<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Rehla\Orders\Queries\ListAllOrders;

final class OrderController extends Controller
{
    public function index(ListAllOrders $listAllOrders): View
    {
        $orders = $listAllOrders->execute();

        return view('rehla-admin::orders.index', [
            'orders' => $orders,
        ]);
    }
}
