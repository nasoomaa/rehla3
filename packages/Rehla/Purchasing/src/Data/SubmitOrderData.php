<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Data;

final readonly class SubmitOrderData
{
    /**
     * @param  array<string, mixed>  $answers
     * @param  list<string>  $documentIds
     */
    public function __construct(
        public string $accountId,
        public string $serviceId,
        public string $travelerId,
        public int $acceptedPriceMinor,
        public int $acceptedPriceVersion,
        public string $formVersionId,
        public string $idempotencyKey,
        public array $answers = [],
        public array $documentIds = [],
    ) {}
}
