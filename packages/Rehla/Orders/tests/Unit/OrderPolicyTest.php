<?php

declare(strict_types=1);

use Rehla\Orders\Data\CreatePaidOrderData;
use Rehla\Orders\Data\FormSnapshotData;
use Rehla\Orders\Data\ServiceSnapshotData;
use Rehla\Orders\Data\TravelerSnapshotData;

it('enforces single traveler per order policy in Phase 1', function (): void {
    $data = new CreatePaidOrderData(
        accountId: '00000000-0000-0000-0000-000000000001',
        serviceId: '00000000-0000-0000-0000-000000000002',
        travelerId: '00000000-0000-0000-0000-000000000003',
        priceMinor: 50000,
        amountPaidMinor: 50000,
        currency: 'SDG',
        debitLedgerEntryId: '00000000-0000-0000-0000-000000000004',
        serviceSnapshot: new ServiceSnapshotData(
            nameEn: 'Service',
            nameAr: 'خدمة',
            descriptions: [],
            requirements: [],
            expectedDuration: [],
            notes: [],
        ),
        travelerSnapshot: new TravelerSnapshotData(
            fullName: 'Solo Traveler',
            dateOfBirth: '1985-06-15',
            gender: 'female',
            passportNumber: 'P7654321',
        ),
        formSnapshot: new FormSnapshotData(
            formVersionId: '00000000-0000-0000-0000-000000000005',
            formVersion: 1,
            formChecksum: 'checksum',
            schema: [],
            answers: [],
        ),
    );

    expect($data->travelerId)->toBe('00000000-0000-0000-0000-000000000003')
        ->and($data->travelerSnapshot)->toBeInstanceOf(TravelerSnapshotData::class);
});
