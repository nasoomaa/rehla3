<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Controllers;

use Illuminate\Contracts\View\View;
use Rehla\Catalog\Queries\ListPublishedServices;

final class HomeController
{
    public function __invoke(ListPublishedServices $listServices): View
    {
        $services = $listServices->execute();

        return view('rehla-web::public.home', [
            'services' => $services,
        ]);
    }
}
