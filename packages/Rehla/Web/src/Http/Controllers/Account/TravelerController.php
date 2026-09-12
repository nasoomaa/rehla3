<?php

declare(strict_types=1);

namespace Rehla\Web\Http\Controllers\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Actions\UpdateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;
use Rehla\Travelers\Exceptions\TravelerNotFound;
use Rehla\Travelers\Queries\GetOwnedTravelerSnapshot;
use Rehla\Travelers\Queries\ListOwnedTravelers;

final class TravelerController
{
    public function index(ListOwnedTravelers $listTravelers): View
    {
        $travelers = $listTravelers->handle((string) Auth::id());

        return view('rehla-web::account.travelers.index', [
            'travelers' => $travelers,
        ]);
    }

    public function create(): View
    {
        return view('rehla-web::account.travelers.form');
    }

    public function store(Request $request, CreateTraveler $createTraveler): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female'],
            'passport_number' => ['required', 'string', 'max:50'],
            'passport_issued_at' => ['required', 'date'],
            'passport_expires_at' => ['required', 'date', 'after:passport_issued_at'],
        ]);

        $createTraveler->handle(new TravelerData(
            ownerId: (string) Auth::id(),
            fullName: $validated['full_name'],
            dateOfBirth: $validated['date_of_birth'],
            gender: Gender::from($validated['gender']),
            passportNumber: $validated['passport_number'],
            passportIssuedAt: $validated['passport_issued_at'],
            passportExpiresAt: $validated['passport_expires_at'],
        ));

        return redirect('/account/travelers')->with('status', 'Traveler created successfully.');
    }

    public function edit(string $id, GetOwnedTravelerSnapshot $getTraveler): View
    {
        try {
            $traveler = $getTraveler->handle((string) Auth::id(), $id);

            return view('rehla-web::account.travelers.form', [
                'traveler' => $traveler,
            ]);
        } catch (TravelerNotFound) {
            abort(404);
        }
    }

    public function update(string $id, Request $request, UpdateTraveler $updateTraveler): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female'],
            'passport_number' => ['required', 'string', 'max:50'],
            'passport_issued_at' => ['required', 'date'],
            'passport_expires_at' => ['required', 'date', 'after:passport_issued_at'],
        ]);

        try {
            $updateTraveler->handle((string) Auth::id(), $id, new TravelerData(
                ownerId: (string) Auth::id(),
                fullName: $validated['full_name'],
                dateOfBirth: $validated['date_of_birth'],
                gender: Gender::from($validated['gender']),
                passportNumber: $validated['passport_number'],
                passportIssuedAt: $validated['passport_issued_at'],
                passportExpiresAt: $validated['passport_expires_at'],
                id: $id,
            ));

            return redirect('/account/travelers')->with('status', 'Traveler updated successfully.');
        } catch (TravelerNotFound) {
            abort(404);
        }
    }
}
