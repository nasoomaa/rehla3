<?php

declare(strict_types=1);

namespace Rehla\Orders\Data;

use DateTimeImmutable;

final readonly class PaidOrderData
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $serviceId,
        public string $travelerId,
        public int $priceMinor,
        public int $amountPaidMinor,
        public string $currency,
        public string $debitLedgerEntryId,
        public string $financialStatus,
        public DateTimeImmutable $createdAt,
        public ServiceSnapshotData $serviceSnapshot,
        public TravelerSnapshotData $travelerSnapshot,
        public FormSnapshotData $formSnapshot,
    ) {}
}
