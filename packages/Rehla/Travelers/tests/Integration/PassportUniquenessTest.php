<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;
use Rehla\Travelers\Exceptions\DuplicatePassport;
use Rehla\Travelers\Exceptions\InvalidTravelerDates;
use Rehla\Travelers\Support\NormalizePassportNumber;

it('normalizes passport strings removing Unicode whitespace and hyphens and converting to uppercase', function (): void {
    expect(NormalizePassportNumber::normalize(" \u{00A0}a-12 34-xy \t"))->toBe('A1234XY');
    expect(NormalizePassportNumber::normalize('n-998877'))->toBe('N998877');
});

it('rejects invalid date combinations for travelers', function (): void {
    $owner = (string) Str::uuid();

    // 1. Issue date after expiry date
    expect(fn () => app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $owner,
        fullName: 'Invalid Traveler',
        dateOfBirth: '1995-01-01',
        gender: Gender::Male,
        passportNumber: 'A111111',
        passportIssuedAt: '2030-01-01',
        passportExpiresAt: '2025-01-01',
    )))->toThrow(InvalidTravelerDates::class);

    // 2. Expiry date before birth date
    expect(fn () => app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $owner,
        fullName: 'Invalid Traveler 2',
        dateOfBirth: '2000-01-01',
        gender: Gender::Female,
        passportNumber: 'B222222',
        passportIssuedAt: '1998-01-01',
        passportExpiresAt: '1999-01-01',
    )))->toThrow(InvalidTravelerDates::class);
});

it('rejects duplicate passport across two concurrent or distinct save attempts', function (): void {
    $owner1 = (string) Str::uuid();
    $owner2 = (string) Str::uuid();

    $t1 = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $owner1,
        fullName: 'First Owner',
        dateOfBirth: '1991-03-10',
        gender: Gender::Male,
        passportNumber: 'k-77 88 99',
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));

    expect($t1->normalizedPassportNumber)->toBe('K778899');

    // Second save with differently spaced string for same passport fails with DuplicatePassport
    expect(fn () => app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $owner2,
        fullName: 'Second Owner',
        dateOfBirth: '1993-04-12',
        gender: Gender::Female,
        passportNumber: 'K778899',
        passportIssuedAt: '2021-01-01',
        passportExpiresAt: '2031-01-01',
    )))->toThrow(DuplicatePassport::class);
});
