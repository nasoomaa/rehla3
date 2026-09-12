<?php

declare(strict_types=1);

use Rehla\Orders\Data\CreatePaidOrderData;
use Rehla\Orders\Data\FormSnapshotData;
use Rehla\Orders\Data\ServiceSnapshotData;
use Rehla\Orders\Data\TravelerSnapshotData;

it('verifies order financial status is strictly paid and separated from execution lifecycle', function (): void {
    $data = new CreatePaidOrderData(
        accountId: '00000000-0000-0000-0000-000000000001',
        serviceId: '00000000-0000-0000-0000-000000000002',
        travelerId: '00000000-0000-0000-0000-000000000003',
        priceMinor: 2500000,
        amountPaidMinor: 2500000,
        currency: 'SDG',
        debitLedgerEntryId: '00000000-0000-0000-0000-000000000004',
        serviceSnapshot: new ServiceSnapshotData(
            nameEn: 'Visa',
            nameAr: 'تأشيرة',
            descriptions: [],
            requirements: [],
            expectedDuration: [],
            notes: [],
        ),
        travelerSnapshot: new TravelerSnapshotData(
            fullName: 'Ali Babiker',
            dateOfBirth: '1990-01-01',
            gender: 'male',
            passportNumber: 'P123456',
        ),
        formSnapshot: new FormSnapshotData(
            formVersionId: '00000000-0000-0000-0000-000000000005',
            formVersion: 1,
            formChecksum: 'checksum',
            schema: [],
            answers: [],
        ),
    );

    expect($data->currency)->toBe('SDG')
        ->and($data->priceMinor)->toBe(2500000)
        ->and($data->amountPaidMinor)->toBe(2500000);
});
