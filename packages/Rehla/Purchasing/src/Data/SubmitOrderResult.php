<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Data;

final readonly class SubmitOrderResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $orderId,
        public string $executionId,
        public string $status,
        public int $amountPaidMinor,
        public string $currency,
        public array $metadata = [],
    ) {}
}
