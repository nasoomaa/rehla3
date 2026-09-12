<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Data;

use DateTimeImmutable;

final readonly class CustomerActionResponseData
{
    public function __construct(
        public string $id,
        public string $actionRequestId,
        public string $accountId,
        public ?string $message,
        public ?string $documentId,
        public DateTimeImmutable $submittedAt,
    ) {}
}
