<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;

final class AuthController
{
    public function showLoginForm(): View
    {
        return view('rehla-web::auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended('/account/profile');
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegisterForm(): View
    {
        return view('rehla-web::auth.register');
    }

    public function register(Request $request, RegisterCustomer $registerCustomer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $userData = $registerCustomer->handle(new RegisterCustomerData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
        ));

        Auth::guard('web')->loginUsingId($userData->id);
        $request->session()->regenerate();

        return redirect('/account/profile');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
