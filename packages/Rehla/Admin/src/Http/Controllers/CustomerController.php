<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Rehla\Identity\Data\UserData;
use Rehla\Identity\Queries\ListCustomers;

final class CustomerController extends Controller
{
    public function index(ListCustomers $listCustomers): View
    {
        $customers = $listCustomers->execute();

        // Project into safe masked view data
        $safeCustomers = array_map(function (UserData $customer): array {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ];
        }, $customers);

        return view('rehla-admin::customers.index', [
            'customers' => $safeCustomers,
        ]);
    }
}
