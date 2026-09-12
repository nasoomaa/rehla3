<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Data\PublishedFormData;
use Rehla\Forms\Data\ValidatedSubmission;
use Rehla\Forms\Enums\FieldType;
use Rehla\Forms\Enums\FormVersionStatus;
use Rehla\Forms\Exceptions\FormValidationFailedException;
use Rehla\Forms\Services\FormValidatorService;

dataset('all 11 field types', [
    ['short_text', FieldType::ShortText, 'Ali Mohamed', 12345],
    ['long_text', FieldType::LongText, 'Detailed travel purpose notes here.', ['invalid' => 'array']],
    ['email', FieldType::Email, 'ali@example.test', 'not-an-email'],
    ['phone', FieldType::Phone, '+249912345678', 'invalid_phone!'],
    ['number', FieldType::Number, 42, 'not-a-number'],
    ['date', FieldType::Date, '2026-10-15', '2026-99-99'],
    ['select', FieldType::Select, 'single_entry', 'invalid_choice'],
    ['radio', FieldType::Radio, 'male', 'other_gender'],
    ['checkbox', FieldType::Checkbox, true, 'not-boolean'],
    ['file', FieldType::File, 'doc-uuid-1', null],
    ['image', FieldType::Image, 'doc-uuid-2', null],
]);

it('validates each of the 11 field types correctly when valid and rejects invalid inputs', function (
    string $typeName,
    FieldType $type,
    mixed $validValue,
    mixed $invalidValue
): void {
    $validator = app(FormValidatorService::class);

    $field = new FormFieldData(
        key: "field_{$typeName}",
        type: $type,
        labelEn: "Label {$typeName}",
        labelAr: "عنوان {$typeName}",
        order: 1,
        required: true,
        options: in_array($type, [FieldType::Select, FieldType::Radio], true)
            ? [
                ['value' => (string) $validValue, 'label_en' => 'Valid Option', 'label_ar' => 'خيار صحيح'],
            ]
            : [],
        validation: in_array($type, [FieldType::File, FieldType::Image], true)
            ? ['document_purpose' => 'passport', 'max_files' => 1]
            : [],
    );

    $formData = new PublishedFormData(
        id: (string) Str::uuid(),
        serviceId: (string) Str::uuid(),
        version: 1,
        status: FormVersionStatus::Published,
        checksum: str_repeat('a', 64),
        fields: [$field],
    );

    // Test Valid Value
    $docIds = in_array($type, [FieldType::File, FieldType::Image], true) ? [(string) $validValue] : [];
    $validSubmission = $validator->validateFormData(
        $formData,
        ["field_{$typeName}" => $validValue],
        $docIds
    );

    expect($validSubmission)->toBeInstanceOf(ValidatedSubmission::class)
        ->and($validSubmission->answers)->toHaveKey("field_{$typeName}");

    // Test Invalid Value
    expect(function () use ($validator, $formData, $typeName, $invalidValue): void {
        $validator->validateFormData(
            $formData,
            ["field_{$typeName}" => $invalidValue],
            []
        );
    })->toThrow(FormValidationFailedException::class);
})->with('all 11 field types');
