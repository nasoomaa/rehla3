<?php

declare(strict_types=1);

namespace Rehla\Forms\Data;

final readonly class ValidatedSubmission
{
    /**
     * @param  array<string, mixed>  $answers
     * @param  array<int, string>  $documentIds
     */
    public function __construct(
        public string $formVersionId,
        public array $answers,
        public array $documentIds = [],
    ) {}
}
