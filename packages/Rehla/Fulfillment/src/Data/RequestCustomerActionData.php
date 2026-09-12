<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Data;

use DateTimeImmutable;
use Rehla\Identity\Data\ActorData;

final readonly class RequestCustomerActionData
{
    public function __construct(
        public string $executionId,
        public ActorData $actor,
        public string $descriptionEn,
        public string $descriptionAr,
        public ?string $requiredDocumentPurpose = null,
        public ?DateTimeImmutable $dueAt = null,
    ) {}
}
