<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LocaleController
{
    public function __invoke(string $locale, Request $request): RedirectResponse
    {
        if (in_array($locale, ['en', 'ar'], true)) {
            session(['locale' => $locale]);
            $request->session()->put('locale', $locale);
            app()->setLocale($locale);
        }

        return redirect()->back(fallback: '/');
    }
}
