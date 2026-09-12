<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rehla\Api\Http\Resources\V1\TravelerResource;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;
use Rehla\Travelers\Exceptions\TravelerNotFound;
use Rehla\Travelers\Queries\GetOwnedTravelerSnapshot;
use Rehla\Travelers\Queries\ListOwnedTravelers;

final class TravelerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $travelers = app(ListOwnedTravelers::class)->handle($request->user()->id);

        return response()->json([
            'data' => TravelerResource::collection($travelers)->resolve(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female'],
            'passport_number' => ['required', 'string', 'max:50'],
            'passport_issued_at' => ['nullable', 'date'],
            'passport_expires_at' => ['required', 'date', 'after:today'],
        ]);

        $traveler = app(CreateTraveler::class)->handle(new TravelerData(
            ownerId: $request->user()->id,
            fullName: $validated['full_name'],
            dateOfBirth: $validated['date_of_birth'],
            gender: Gender::from($validated['gender']),
            passportNumber: $validated['passport_number'],
            passportIssuedAt: $validated['passport_issued_at'] ?? '2020-01-01',
            passportExpiresAt: $validated['passport_expires_at'],
        ));

        return response()->json([
            'data' => (new TravelerResource($traveler))->resolve(),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $snapshot = app(GetOwnedTravelerSnapshot::class)->handle($request->user()->id, $id);
        } catch (TravelerNotFound $e) {
            abort(404);
        }

        return response()->json([
            'data' => (new TravelerResource($snapshot))->resolve(),
        ]);
    }
}
