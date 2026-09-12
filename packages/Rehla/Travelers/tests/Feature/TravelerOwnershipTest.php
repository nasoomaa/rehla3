<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rehla\Core\Errors\ErrorCode;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;
use Rehla\Travelers\Exceptions\DuplicatePassport;
use Rehla\Travelers\Exceptions\TravelerNotFound;
use Rehla\Travelers\Queries\GetOwnedTravelerSnapshot;

it('normalizes passport globally without revealing another owner', function (): void {
    $ownerA = (string) Str::uuid();
    $ownerB = (string) Str::uuid();

    $travelerA = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $ownerA,
        fullName: 'Mohammed Ali',
        dateOfBirth: '1990-05-15',
        gender: Gender::Male,
        passportNumber: ' p-12 34 ',
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));

    expect($travelerA->normalizedPassportNumber)->toBe('P1234');

    // Attempting to register the same passport from owner B must throw DuplicatePassport with DUPLICATE_PASSPORT code
    expect(fn () => app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $ownerB,
        fullName: 'Same Passport Person',
        dateOfBirth: '1992-06-20',
        gender: Gender::Female,
        passportNumber: 'P1234',
        passportIssuedAt: '2021-01-01',
        passportExpiresAt: '2031-01-01',
    )))->toThrow(DuplicatePassport::class, ErrorCode::DUPLICATE_PASSPORT->value);

    // Owner B cannot inspect owner A's traveler snapshot — returns TravelerNotFound
    expect(fn () => app(GetOwnedTravelerSnapshot::class)->handle($ownerB, $travelerA->id))
        ->toThrow(TravelerNotFound::class);

    // Owner A can retrieve own traveler snapshot
    $snapshot = app(GetOwnedTravelerSnapshot::class)->handle($ownerA, $travelerA->id);
    expect($snapshot->fullName)->toBe('Mohammed Ali')
        ->and($snapshot->passportNumber)->toBe('P1234')
        ->and($snapshot->gender)->toBe(Gender::Male);
});
