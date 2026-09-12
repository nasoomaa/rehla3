<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rehla\Orders\Contracts\OrderWriter;
use Rehla\Orders\Data\CreatePaidOrderData;
use Rehla\Orders\Data\FormSnapshotData;
use Rehla\Orders\Data\ServiceSnapshotData;
use Rehla\Orders\Data\TravelerSnapshotData;
use Rehla\Orders\Exceptions\OrderNotFoundException;
use Rehla\Orders\Queries\GetOwnedOrder;
use Rehla\Orders\Queries\ListOwnedOrders;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

function validPaidOrderDataForOwnership(
    int $priceMinor = 25_000_00,
    string $travelerName = 'Ahmed Ali',
    int $formVersion = 1,
    ?string $accountId = null,
): CreatePaidOrderData {
    return new CreatePaidOrderData(
        accountId: $accountId ?? (string) Str::uuid(),
        serviceId: (string) Str::uuid(),
        travelerId: (string) Str::uuid(),
        priceMinor: $priceMinor,
        amountPaidMinor: $priceMinor,
        currency: 'SDG',
        debitLedgerEntryId: (string) Str::uuid(),
        serviceSnapshot: new ServiceSnapshotData(
            nameEn: 'Passport Renewal',
            nameAr: 'تجديد جواز السفر',
            descriptions: ['summary' => 'Standard renewal'],
            requirements: ['photos' => 2],
            expectedDuration: ['days' => 5],
            notes: ['Urgent processing available'],
        ),
        travelerSnapshot: new TravelerSnapshotData(
            fullName: $travelerName,
            dateOfBirth: '1990-01-15',
            gender: 'male',
            passportNumber: 'P12345678',
            passportIssuedAt: '2020-01-01',
            passportExpiresAt: '2030-01-01',
        ),
        formSnapshot: new FormSnapshotData(
            formVersionId: (string) Str::uuid(),
            formVersion: $formVersion,
            formChecksum: hash('sha256', 'test-form-schema'),
            schema: ['fields' => []],
            answers: ['purpose' => 'tourism'],
        ),
    );
}

it('retrieves an owned order and prevents other accounts from accessing it', function (): void {
    $ownerA = (string) Str::uuid();
    $ownerB = (string) Str::uuid();

    $orderWriter = app(OrderWriter::class);
    $orderA = $orderWriter->createPaid(validPaidOrderDataForOwnership(priceMinor: 15_000_00, accountId: $ownerA));

    $getter = app(GetOwnedOrder::class);

    // Owner A can retrieve their order
    $fetched = $getter->handle($ownerA, $orderA->id);
    expect($fetched->id)->toBe($orderA->id)
        ->and($fetched->accountId)->toBe($ownerA)
        ->and($fetched->priceMinor)->toBe(15_000_00);

    // Owner B gets OrderNotFoundException (identical to 404, never leaking existence)
    expect(fn () => $getter->handle($ownerB, $orderA->id))
        ->toThrow(OrderNotFoundException::class);

    // Non-existent order also throws OrderNotFoundException
    expect(fn () => $getter->handle($ownerA, (string) Str::uuid()))
        ->toThrow(OrderNotFoundException::class);
});

it('lists only orders owned by the specified account', function (): void {
    $ownerA = (string) Str::uuid();
    $ownerB = (string) Str::uuid();

    $orderWriter = app(OrderWriter::class);
    $orderWriter->createPaid(validPaidOrderDataForOwnership(priceMinor: 10_000_00, accountId: $ownerA));
    $orderWriter->createPaid(validPaidOrderDataForOwnership(priceMinor: 20_000_00, accountId: $ownerA));
    $orderWriter->createPaid(validPaidOrderDataForOwnership(priceMinor: 30_000_00, accountId: $ownerB));

    $lister = app(ListOwnedOrders::class);

    $ordersA = $lister->handle($ownerA);
    expect($ordersA)->toHaveCount(2);

    $ordersB = $lister->handle($ownerB);
    expect($ordersB)->toHaveCount(1);

    $ordersC = $lister->handle((string) Str::uuid());
    expect($ordersC)->toHaveCount(0);
});
