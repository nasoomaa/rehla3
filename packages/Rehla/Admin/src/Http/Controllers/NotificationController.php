<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Rehla\Notifications\Queries\ListAllNotifications;

final class NotificationController extends Controller
{
    public function index(ListAllNotifications $listAllNotifications): View
    {
        $notifications = $listAllNotifications->execute();

        return view('rehla-admin::notifications.index', [
            'notifications' => $notifications,
        ]);
    }
}
