<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Orders\Contracts\OrderWriter;
use Rehla\Orders\Data\CreatePaidOrderData;
use Rehla\Orders\Data\FormSnapshotData;
use Rehla\Orders\Data\ServiceSnapshotData;
use Rehla\Orders\Data\TravelerSnapshotData;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

function validPaidOrderData(
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

it('creates a paid order with snapshots and keeps records immutable', function (): void {
    $orderWriter = app(OrderWriter::class);
    $order = $orderWriter->createPaid(validPaidOrderData());

    expect($order->id)->not->toBeEmpty()
        ->and($order->priceMinor)->toBe(25_000_00)
        ->and($order->currency)->toBe('SDG')
        ->and($order->financialStatus)->toBe('paid')
        ->and($order->travelerSnapshot->fullName)->toBe('Ahmed Ali');

    // Attempt direct SQL update on orders
    expect(fn () => DB::table('orders')->where('id', $order->id)->update(['price_minor' => 30_000_00]))
        ->toThrow(QueryException::class);

    // Attempt direct SQL delete on orders
    expect(fn () => DB::table('orders')->where('id', $order->id)->delete())
        ->toThrow(QueryException::class);

    // Attempt direct SQL update on service snapshots
    expect(fn () => DB::table('order_service_snapshots')->where('order_id', $order->id)->update(['name_en' => 'Changed']))
        ->toThrow(QueryException::class);

    // Attempt direct SQL delete on traveler snapshots
    expect(fn () => DB::table('order_traveler_snapshots')->where('order_id', $order->id)->delete())
        ->toThrow(QueryException::class);

    // Attempt direct SQL update on form snapshots
    expect(fn () => DB::table('order_form_snapshots')->where('order_id', $order->id)->update(['form_version' => 99]))
        ->toThrow(QueryException::class);
});
