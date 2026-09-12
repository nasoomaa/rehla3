<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Controllers\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Rehla\TopUps\Queries\ListOwnedTopUps;

final class TopUpController
{
    public function index(ListOwnedTopUps $listTopUps): View
    {
        $topUps = $listTopUps->execute((string) Auth::id());

        return view('rehla-web::account.top-ups.index', [
            'topUps' => $topUps,
        ]);
    }
}
