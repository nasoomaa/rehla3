<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Data;

final readonly class CreateExecutionData
{
    /**
     * @param  array<string, mixed>  $submissionAnswers
     * @param  list<string>  $documentIds
     */
    public function __construct(
        public string $orderId,
        public string $accountId,
        public string $travelerId,
        public string $serviceId,
        public string $formVersionId,
        public array $submissionAnswers = [],
        public array $documentIds = [],
    ) {}
}
