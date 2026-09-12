<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Data;

use DateTimeImmutable;

final readonly class ExecutionDetails
{
    /**
     * @param  list<StatusHistoryEntryData>  $history
     * @param  list<CustomerActionRequestData>  $actionRequests
     * @param  list<string>  $attachedDocumentIds
     */
    public function __construct(
        public string $id,
        public string $orderId,
        public string $accountId,
        public string $travelerId,
        public string $serviceId,
        public string $formVersionId,
        public string $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $lastStatusAt,
        public array $history = [],
        public array $actionRequests = [],
        public array $attachedDocumentIds = [],
    ) {}
}
