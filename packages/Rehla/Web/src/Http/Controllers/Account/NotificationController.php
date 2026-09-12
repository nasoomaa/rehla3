<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Controllers\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Rehla\Notifications\Queries\ListOwnedNotifications;

final class NotificationController
{
    public function index(ListOwnedNotifications $listNotifications): View
    {
        $notifications = $listNotifications->execute((string) Auth::id());

        return view('rehla-web::account.notifications.index', [
            'notifications' => $notifications,
        ]);
    }
}
