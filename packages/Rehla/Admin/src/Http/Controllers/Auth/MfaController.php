<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Rehla\Identity\Actions\ConfirmStaffMfa;

final class MfaController extends Controller
{
    public function show(): View
    {
        return view('rehla-admin::auth.mfa');
    }

    public function confirm(Request $request, ConfirmStaffMfa $confirmStaffMfa): RedirectResponse
    {
        $validated = $request->validate([
            'totp_code' => ['required', 'string'],
        ]);

        $adminId = (string) Auth::guard('admin')->id();
        $confirmStaffMfa->handle($adminId, $validated['totp_code']);

        // Refresh user in auth guard
        $freshUser = Auth::guard('admin')->getProvider()->retrieveById($adminId);
        if ($freshUser !== null) {
            Auth::guard('admin')->setUser($freshUser);
        }

        return redirect('/admin')->with('success', 'MFA verified successfully.');
    }
}
