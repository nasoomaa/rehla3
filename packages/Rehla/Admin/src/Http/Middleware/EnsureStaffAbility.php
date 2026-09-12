<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class EnsureStaffAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = Auth::guard('admin')->user();
        if ($user === null) {
            return redirect()->guest('/admin/login');
        }

        if (! Gate::forUser($user)->allows($ability)) {
            abort(403, 'Forbidden: Missing capability or fresh MFA confirmation required.');
        }

        return $next($request);
    }
}
