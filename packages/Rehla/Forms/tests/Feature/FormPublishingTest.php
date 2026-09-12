<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rehla\Forms\Actions\CreateFormDraft;
use Rehla\Forms\Actions\PublishFormVersion;
use Rehla\Forms\Actions\UpdateFormDraft;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Enums\FieldType;
use Rehla\Forms\Enums\FormVersionStatus;
use Rehla\Forms\Queries\GetPublishedForm;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('creates a draft, allows updates, and publishes immutable version with checksum', function (): void {
    $serviceId = (string) Str::uuid();
    $actorId = (string) Str::uuid();

    $createDraft = app(CreateFormDraft::class);
    $updateDraft = app(UpdateFormDraft::class);
    $publishVersion = app(PublishFormVersion::class);
    $getPublished = app(GetPublishedForm::class);

    $fields = [
        new FormFieldData(
            key: 'full_name',
            type: FieldType::ShortText,
            labelEn: 'Full Name',
            labelAr: 'الاسم الكامل',
            order: 1,
            required: true,
            helperEn: 'Enter name as on passport',
            helperAr: 'أدخل الاسم كما هو في الجواز',
        ),
        new FormFieldData(
            key: 'passport_scan',
            type: FieldType::File,
            labelEn: 'Passport Scan',
            labelAr: 'صورة الجواز',
            order: 2,
            required: true,
            validation: ['document_purpose' => 'passport', 'max_files' => 1],
        ),
    ];

    $draft = $createDraft->execute($serviceId, $fields, $actorId);

    expect($draft->serviceId)->toBe($serviceId)
        ->and($draft->status)->toBe(FormVersionStatus::Draft)
        ->and($draft->fields)->toHaveCount(2);

    // Update draft with additional field
    $updatedFields = [
        ...$fields,
        new FormFieldData(
            key: 'travel_date',
            type: FieldType::Date,
            labelEn: 'Travel Date',
            labelAr: 'تاريخ السفر',
            order: 3,
            required: false,
        ),
    ];

    $updatedDraft = $updateDraft->execute($draft->id, $updatedFields, $actorId);
    expect($updatedDraft->fields)->toHaveCount(3);

    // Publish version
    $published = $publishVersion->execute($draft->id, $actorId);

    expect($published->status)->toBe(FormVersionStatus::Published)
        ->and($published->version)->toBe(1)
        ->and($published->checksum)->toHaveLength(64)
        ->and($published->publishedAt)->not->toBeNull();

    // Query active published form
    $activeForm = $getPublished->handle($serviceId);
    expect($activeForm)->not->toBeNull()
        ->and($activeForm->id)->toBe($published->id)
        ->and($activeForm->version)->toBe(1)
        ->and($activeForm->fields)->toHaveCount(3);
});
