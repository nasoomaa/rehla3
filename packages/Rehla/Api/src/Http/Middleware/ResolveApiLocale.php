<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Accept-Language', 'en');
        $primary = strtolower(trim(explode(',', explode(';', $header)[0])[0]));

        $locale = str_starts_with($primary, 'ar') ? 'ar' : 'en';

        app()->setLocale($locale);

        return $next($request);
    }
}
