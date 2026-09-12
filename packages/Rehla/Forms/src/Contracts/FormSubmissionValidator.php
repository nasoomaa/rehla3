<?php

declare(strict_types=1);

namespace Rehla\Forms\Contracts;

use Rehla\Forms\Data\PublishedFormData;
use Rehla\Forms\Data\ValidatedSubmission;

interface FormSubmissionValidator
{
    /**
     * @param  array<string, mixed>  $answers
     * @param  array<int, string>  $documentIds
     */
    public function validate(string $formVersionId, array $answers, array $documentIds = []): ValidatedSubmission;

    /**
     * @param  array<string, mixed>  $answers
     * @param  array<int, string>  $documentIds
     */
    public function validateFormData(PublishedFormData $form, array $answers, array $documentIds = []): ValidatedSubmission;
}
