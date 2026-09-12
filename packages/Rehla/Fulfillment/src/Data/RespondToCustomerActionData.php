<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Data;

final readonly class RespondToCustomerActionData
{
    public function __construct(
        public string $actionRequestId,
        public string $accountId,
        public ?string $message = null,
        public ?string $documentId = null,
    ) {}
}
