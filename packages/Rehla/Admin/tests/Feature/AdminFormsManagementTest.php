<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Forms\Queries\ListFormVersions;
use Tests\Support\StaffTestHelper;

it('creates, updates draft schema, and publishes application form versions', function (): void {
    $staff = StaffTestHelper::createStaff(abilities: ['forms.manage']);
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    // Create service first to attach form
    $serviceId = (string) Str::uuid();
    DB::table('services')->insert([
        'id' => $serviceId,
        'slug' => 'visa-service-'.Str::random(5),
        'name_en' => 'Sample Visa',
        'name_ar' => 'تأشيرة نموذج',
        'short_description_en' => 'Short EN',
        'short_description_ar' => 'Short AR',
        'detailed_description_en' => 'Detailed EN',
        'detailed_description_ar' => 'Detailed AR',
        'expected_duration_en' => '3 days',
        'expected_duration_ar' => '3 أيام',
        'status' => 'draft',
        'current_price_minor' => 1000000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 1. Create draft
    $createRes = $this->actingAs($user, 'admin')->post("/admin/application-forms/{$serviceId}/draft", [
        'schema_json' => json_encode([
            'fields' => [
                ['key' => 'passport_no', 'type' => 'short_text', 'label_en' => 'Passport No', 'label_ar' => 'رقم الجواز', 'required' => true],
            ],
        ]),
    ]);
    $createRes->assertRedirect('/admin/application-forms');

    $forms = app(ListFormVersions::class)->execute();
    $draft = collect($forms)->firstWhere('serviceId', $serviceId);
    expect($draft)->not->toBeNull();

    // 2. Update draft schema
    $updateRes = $this->actingAs($user, 'admin')->put("/admin/application-forms/{$draft->id}/draft", [
        'schema_json' => json_encode([
            'fields' => [
                ['key' => 'passport_no', 'type' => 'short_text', 'label_en' => 'Passport No', 'label_ar' => 'رقم الجواز', 'required' => true],
                ['key' => 'nationality', 'type' => 'short_text', 'label_en' => 'Nationality', 'label_ar' => 'الجنسية', 'required' => true],
            ],
        ]),
    ]);
    $updateRes->assertRedirect('/admin/application-forms');

    $reloadedDrafts = app(ListFormVersions::class)->execute();
    $reloadedDraft = collect($reloadedDrafts)->firstWhere('id', $draft->id);
    expect($reloadedDraft)->not->toBeNull();
    $fieldKeys = collect($reloadedDraft->fields)->pluck('key')->all();
    expect($fieldKeys)->toContain('nationality');

    // 3. Publish form version
    $publishRes = $this->actingAs($user, 'admin')->post("/admin/application-forms/{$draft->id}/publish");
    $publishRes->assertRedirect('/admin/application-forms');

    $updatedForms = app(ListFormVersions::class)->execute();
    $published = collect($updatedForms)->firstWhere('id', $draft->id);
    expect($published->status->value)->toBe('published');
});
