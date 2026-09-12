<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (! $locale && $request->has('lang')) {
            $lang = (string) $request->query('lang');
            if (in_array($lang, ['en', 'ar'], true)) {
                $locale = $lang;
                $request->session()->put('locale', $locale);
            }
        }

        if ($locale && in_array($locale, ['en', 'ar'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
