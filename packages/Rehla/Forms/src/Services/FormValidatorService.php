<?php

declare(strict_types=1);

namespace Rehla\Forms\Services;

use DateTimeImmutable;
use DomainException;
use Rehla\Forms\Contracts\FormSubmissionValidator;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Data\PublishedFormData;
use Rehla\Forms\Data\ValidatedSubmission;
use Rehla\Forms\Enums\FieldType;
use Rehla\Forms\Exceptions\FormValidationFailedException;
use Rehla\Forms\Models\FormVersion;

final class FormValidatorService implements FormSubmissionValidator
{
    public function validate(string $formVersionId, array $answers, array $documentIds = []): ValidatedSubmission
    {
        /** @var FormVersion|null $version */
        $version = FormVersion::find($formVersionId);
        if (! $version) {
            throw new DomainException("Form version not found: {$formVersionId}");
        }

        return $this->validateFormData($version->toPublishedData(), $answers, $documentIds);
    }

    public function validateFormData(PublishedFormData $form, array $answers, array $documentIds = []): ValidatedSubmission
    {
        $validated = [];
        $collectedDocIds = [];
        $errors = [];

        foreach ($form->fields as $field) {
            $key = $field->key;
            $hasKey = array_key_exists($key, $answers);
            $val = $hasKey ? $answers[$key] : null;

            // Required check
            if ($field->required) {
                if (! $hasKey || $val === null || (is_string($val) && trim($val) === '')) {
                    $errors[$key][] = "Field '{$key}' is required.";

                    continue;
                }
            } else {
                if (! $hasKey || $val === null || (is_string($val) && trim($val) === '')) {
                    $validated[$key] = null;

                    continue;
                }
            }

            // Type check
            $fieldError = $this->validateFieldValue($field, $val);
            if ($fieldError !== null) {
                $errors[$key][] = $fieldError;

                continue;
            }

            // Document collections
            if (in_array($field->type, [FieldType::File, FieldType::Image], true)) {
                $docId = (string) $val;
                if (! empty($documentIds) && ! in_array($docId, $documentIds, true)) {
                    $errors[$key][] = "Document '{$docId}' not recognized in uploaded session.";

                    continue;
                }
                $collectedDocIds[] = $docId;
            }

            $validated[$key] = $val;
        }

        if (! empty($errors)) {
            throw new FormValidationFailedException(
                'Form validation failed for one or more fields.',
                $errors
            );
        }

        return new ValidatedSubmission(
            formVersionId: $form->id,
            answers: $validated,
            documentIds: array_unique($collectedDocIds),
        );
    }

    private function validateFieldValue(FormFieldData $field, mixed $val): ?string
    {
        return match ($field->type) {
            FieldType::ShortText => is_string($val) && mb_strlen($val) <= 255
                ? null
                : 'Expected a string up to 255 characters.',

            FieldType::LongText => is_string($val)
                ? null
                : 'Expected text content.',

            FieldType::Email => is_string($val) && filter_var($val, FILTER_VALIDATE_EMAIL) !== false
                ? null
                : 'Expected a valid email address.',

            FieldType::Phone => is_string($val) && preg_match('/^\+?[0-9\s\-]{7,20}$/', $val)
                ? null
                : 'Expected a valid phone number.',

            FieldType::Number => is_int($val) || (is_string($val) && is_numeric($val))
                ? null
                : 'Expected a numeric value.',

            FieldType::Date => $this->isValidDate($val)
                ? null
                : 'Expected a valid ISO date (YYYY-MM-DD).',

            FieldType::Select, FieldType::Radio => $this->isInOptions($field, $val)
                ? null
                : 'Selected value is not in permitted options.',

            FieldType::Checkbox => is_bool($val) || in_array($val, [1, 0, '1', '0', 'true', 'false'], true)
                ? null
                : 'Expected a boolean value.',

            FieldType::File, FieldType::Image => is_string($val) && trim($val) !== ''
                ? null
                : 'Expected a valid document reference identifier.',
        };
    }

    private function isValidDate(mixed $val): bool
    {
        if (! is_string($val)) {
            return false;
        }

        $d = DateTimeImmutable::createFromFormat('Y-m-d', $val);

        return $d !== false && $d->format('Y-m-d') === $val;
    }

    private function isInOptions(FormFieldData $field, mixed $val): bool
    {
        $allowedValues = array_column($field->options, 'value');

        return in_array((string) $val, array_map('strval', $allowedValues), true);
    }
}
