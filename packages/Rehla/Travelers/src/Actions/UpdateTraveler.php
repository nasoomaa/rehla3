<?php

declare(strict_types=1);

namespace Rehla\Travelers\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Exceptions\DuplicatePassport;
use Rehla\Travelers\Exceptions\InvalidTravelerDates;
use Rehla\Travelers\Exceptions\TravelerNotFound;
use Rehla\Travelers\Models\Traveler;
use Rehla\Travelers\Support\NormalizePassportNumber;

final class UpdateTraveler
{
    public function handle(string $travelerId, TravelerData $data): TravelerData
    {
        /** @var Traveler|null $traveler */
        $traveler = Traveler::where('id', $travelerId)
            ->where('owner_id', $data->ownerId)
            ->first();

        if ($traveler === null) {
            throw new TravelerNotFound("Traveler not found for account {$data->ownerId}");
        }

        $dob = CarbonImmutable::parse($data->dateOfBirth);
        $issued = CarbonImmutable::parse($data->passportIssuedAt);
        $expires = CarbonImmutable::parse($data->passportExpiresAt);

        if ($dob->isFuture()) {
            throw new InvalidTravelerDates('Date of birth cannot be in the future');
        }

        if ($issued->isAfter($expires)) {
            throw new InvalidTravelerDates('Passport issue date cannot be after expiry date');
        }

        if ($expires->isBefore($dob)) {
            throw new InvalidTravelerDates('Passport expiry date cannot be before birth date');
        }

        if ($issued->isBefore($dob)) {
            throw new InvalidTravelerDates('Passport issue date cannot be before birth date');
        }

        $normalized = NormalizePassportNumber::normalize($data->passportNumber);

        if (Traveler::where('normalized_passport_number', $normalized)->where('id', '!=', $travelerId)->exists()) {
            throw new DuplicatePassport;
        }

        try {
            $traveler->update([
                'full_name' => trim($data->fullName),
                'date_of_birth' => $dob->format('Y-m-d'),
                'gender' => $data->gender,
                'passport_number' => trim($data->passportNumber),
                'normalized_passport_number' => $normalized,
                'passport_issued_at' => $issued->format('Y-m-d'),
                'passport_expires_at' => $expires->format('Y-m-d'),
            ]);

            return $traveler->fresh()->toData();
        } catch (QueryException $e) {
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'Duplicate')) {
                throw new DuplicatePassport;
            }

            throw $e;
        }
    }
}
