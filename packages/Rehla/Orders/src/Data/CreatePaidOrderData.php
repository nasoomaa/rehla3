<?php

declare(strict_types=1);

namespace Rehla\Orders\Data;

final readonly class CreatePaidOrderData
{
    public function __construct(
        public string $accountId,
        public string $serviceId,
        public string $travelerId,
        public int $priceMinor,
        public int $amountPaidMinor,
        public string $currency,
        public string $debitLedgerEntryId,
        public ServiceSnapshotData $serviceSnapshot,
        public TravelerSnapshotData $travelerSnapshot,
        public FormSnapshotData $formSnapshot,
        public ?string $id = null,
    ) {}
}
