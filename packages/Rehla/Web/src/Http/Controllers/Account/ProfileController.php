<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Controllers\Account;

use Illuminate\Contracts\View\View;

final class ProfileController
{
    public function __invoke(): View
    {
        return view('rehla-web::account.profile');
    }
}
