<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class LogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        if ($user !== null && method_exists($user, 'currentAccessToken')) {
            $user->currentAccessToken()?->delete();
        }

        auth('sanctum')->forgetUser();

        return response()->noContent();
    }
}
