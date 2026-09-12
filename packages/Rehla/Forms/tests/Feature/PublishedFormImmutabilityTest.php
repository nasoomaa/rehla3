<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Forms\Actions\CreateFormDraft;
use Rehla\Forms\Actions\PublishFormVersion;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Enums\FieldType;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('prevents direct SQL updates and deletes on published form versions via PostgreSQL trigger', function (): void {
    $createDraft = app(CreateFormDraft::class);
    $publishVersion = app(PublishFormVersion::class);

    $serviceId = (string) Str::uuid();
    $actorId = (string) Str::uuid();

    $fields = [
        new FormFieldData(
            key: 'applicant_name',
            type: FieldType::ShortText,
            labelEn: 'Applicant Name',
            labelAr: 'اسم مقدم الطلب',
            order: 1,
            required: true,
        ),
    ];

    $draft = $createDraft->execute($serviceId, $fields, $actorId);
    $published = $publishVersion->execute($draft->id, $actorId);

    // Attempt direct SQL UPDATE on published row
    expect(function () use ($published): void {
        DB::table('form_versions')
            ->where('id', $published->id)
            ->update(['checksum' => str_repeat('0', 64)]);
    })->toThrow(QueryException::class);

    // Attempt direct SQL DELETE on published row
    expect(function () use ($published): void {
        DB::table('form_versions')
            ->where('id', $published->id)
            ->delete();
    })->toThrow(QueryException::class);
});
