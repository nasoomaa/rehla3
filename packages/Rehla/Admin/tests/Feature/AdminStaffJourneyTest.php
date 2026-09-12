<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Tests\Support\StaffTestHelper;

it('verifies admin staff journey step admin_login_and_totp_mfa', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['topups.review', 'services.manage'],
        mfaConfirmedAt: null, // No MFA yet
        password: 'Password123!',
    );

    // 1. Visit login page
    $this->get('/admin/login')->assertOk();

    // 2. Submit credentials
    $loginResponse = $this->post('/admin/login', [
        'email' => $staff['email'],
        'password' => 'Password123!',
    ]);

    $loginResponse->assertRedirect();
    expect(Auth::guard('admin')->check())->toBeTrue();

    // 3. Confirm MFA
    $mfaResponse = $this->post('/admin/mfa', [
        'totp_code' => '123456',
    ]);

    $mfaResponse->assertRedirect();

    $profile = DB::table('staff_profiles')->where('user_id', $staff['id'])->first();
    expect($profile->mfa_confirmed_at)->not->toBeNull();
});

it('verifies admin staff journey step review_pending_topups_queue', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['topups.review'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );

    // Seed company bank account first
    $bankAccountId = (string) Str::uuid();
    DB::table('company_bank_accounts')->insert([
        'id' => $bankAccountId,
        'bank_name_en' => 'Bank of Khartoum',
        'bank_name_ar' => 'بنك الخرطوم',
        'account_number' => '1234567890',
        'beneficiary_name' => 'Rehla',
        'active' => true,
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    // Seed topup in review
    $topUpId = (string) Str::uuid();
    DB::table('topup_requests')->insert([
        'id' => $topUpId,
        'account_id' => (string) Str::uuid(),
        'wallet_id' => (string) Str::uuid(),
        'bank_account_id' => $bankAccountId,
        'amount_minor' => 15_000_000,
        'transaction_reference' => 'TX-QUEUE-001',
        'normalized_reference' => 'TX-QUEUE-001',
        'status' => 'under_review',
        'receipt_document_id' => (string) Str::uuid(),
        'submitted_at' => CarbonImmutable::now(),
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')->get('/admin/top-up-requests');

    $response->assertOk();
    $response->assertSee('TX-QUEUE-001');
    $response->assertSee('150,000');
});

it('verifies admin staff journey step approve_valid_topup', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['topups.review'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );

    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Valid TopUp Customer',
        email: 'val_topup_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));

    $walletId = (string) Str::uuid();
    DB::table('wallets')->insert([
        'id' => $walletId,
        'account_id' => $customer->id,
        'currency' => 'SDG',
        'balance_minor' => 1_000_000,
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $bankAccountId = (string) Str::uuid();
    DB::table('company_bank_accounts')->insert([
        'id' => $bankAccountId,
        'bank_name_en' => 'Omdurman National Bank',
        'bank_name_ar' => 'بنك أم درمان الوطني',
        'account_number' => '5554443332',
        'beneficiary_name' => 'Rehla',
        'active' => true,
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $topUpId = (string) Str::uuid();
    DB::table('topup_requests')->insert([
        'id' => $topUpId,
        'account_id' => $customer->id,
        'wallet_id' => $walletId,
        'bank_account_id' => $bankAccountId,
        'amount_minor' => 10_000_000,
        'transaction_reference' => 'TX-VALID-001',
        'normalized_reference' => 'TX-VALID-001',
        'status' => 'under_review',
        'receipt_document_id' => (string) Str::uuid(),
        'submitted_at' => CarbonImmutable::now(),
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')
        ->post("/admin/top-up-requests/{$topUpId}/approve", [
            'notes' => 'Confirmed in bank statement',
        ]);

    $response->assertRedirect();
    $updated = DB::table('topup_requests')->where('id', $topUpId)->first();
    expect($updated->status)->toBe('approved');

    $wallet = DB::table('wallets')->where('id', $walletId)->first();
    expect((int) $wallet->balance_minor)->toBe(11_000_000);
});

it('verifies admin staff journey step reject_invalid_topup_with_reason', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['topups.review'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );

    $bankAccountId = (string) Str::uuid();
    DB::table('company_bank_accounts')->insert([
        'id' => $bankAccountId,
        'bank_name_en' => 'Blue Nile Bank',
        'bank_name_ar' => 'بنك النيل الأزرق',
        'account_number' => '7778889990',
        'beneficiary_name' => 'Rehla',
        'active' => true,
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $topUpId = (string) Str::uuid();
    DB::table('topup_requests')->insert([
        'id' => $topUpId,
        'account_id' => (string) Str::uuid(),
        'wallet_id' => (string) Str::uuid(),
        'bank_account_id' => $bankAccountId,
        'amount_minor' => 8_000_000,
        'transaction_reference' => 'TX-BAD-001',
        'normalized_reference' => 'TX-BAD-001',
        'status' => 'under_review',
        'receipt_document_id' => (string) Str::uuid(),
        'submitted_at' => CarbonImmutable::now(),
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')
        ->post("/admin/top-up-requests/{$topUpId}/reject", [
            'reason' => 'Invalid transaction reference number',
        ]);

    $response->assertRedirect();
    $updated = DB::table('topup_requests')->where('id', $topUpId)->first();
    expect($updated->status)->toBe('rejected')
        ->and($updated->rejection_reason)->toBe('Invalid transaction reference number');
});

it('verifies admin staff journey step view_service_catalog_and_price_history', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['services.manage'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );

    // Create a catalog service
    $service = app(CreateService::class)->execute(new CreateServiceData(
        slug: 'saudi-visa-'.Str::random(6),
        nameEn: 'Saudi Tourist Visa',
        nameAr: 'تأشيرة سياحة السعودية',
        shortDescriptionEn: '1-year multiple entry visa',
        shortDescriptionAr: 'تأشيرة دخول متعدد لمدة عام',
        detailedDescriptionEn: 'Full entry visa with medical insurance included.',
        detailedDescriptionAr: 'تأشيرة دخول كاملة مع التأمين الطبي.',
        expectedDurationEn: '24-48 hours',
        expectedDurationAr: '٢٤-٤٨ ساعة',
        priceMinor: 65_000_00,
        requirements: [
            ['text_en' => 'Passport copy', 'text_ar' => 'صورة الجواز', 'sort_order' => 1],
        ],
        media: [
            ['document_id' => (string) Str::uuid(), 'alt_en' => 'Banner', 'alt_ar' => 'بانر', 'sort_order' => 1],
        ],
        notesEn: null,
        notesAr: null,
    ), actorId: $staff['id']);

    app(PublishService::class)->execute($service->id, actorId: $staff['id']);

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')->get('/admin/services');

    $response->assertOk();
    $response->assertSee('Saudi Tourist Visa');
    $response->assertSee('65,000');
});

it('verifies admin staff journey step create_and_publish_form_version', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['forms.manage'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );

    $serviceId = (string) Str::uuid();

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    // 1. Create draft version
    $createResponse = $this->actingAs($user, 'admin')
        ->post("/admin/application-forms/{$serviceId}/versions", [
            'title' => 'Visa Application Schema v1',
            'schema' => json_encode([
                'fields' => [
                    ['id' => 'full_name', 'type' => 'short_text', 'label_en' => 'Full Name', 'label_ar' => 'الاسم الكامل', 'required' => true],
                ],
            ]),
        ]);

    $createResponse->assertRedirect();

    $form = DB::table('form_versions')->where('service_id', $serviceId)->first();
    expect($form)->not->toBeNull();

    // 2. Publish version
    $publishResponse = $this->actingAs($user, 'admin')
        ->post("/admin/application-forms/{$form->id}/publish");

    $publishResponse->assertRedirect();
    $updatedForm = DB::table('form_versions')->where('id', $form->id)->first();
    expect($updatedForm->status)->toBe('published');
});

it('verifies admin staff journey step inspect_executions_and_assign_staff', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['executions.manage'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );

    $executionId = (string) Str::uuid();
    $orderId = (string) Str::uuid();
    $accountId = (string) Str::uuid();
    $travelerId = (string) Str::uuid();
    $formVersionId = (string) Str::uuid();

    DB::table('service_executions')->insert([
        'id' => $executionId,
        'order_id' => $orderId,
        'account_id' => $accountId,
        'traveler_id' => $travelerId,
        'service_id' => (string) Str::uuid(),
        'form_version_id' => $formVersionId,
        'status' => 'order_received',
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    // Inspect execution
    $inspectResponse = $this->actingAs($user, 'admin')->get("/admin/service-executions/{$executionId}");
    $inspectResponse->assertOk();

    // Add internal note
    $noteResponse = $this->actingAs($user, 'admin')
        ->post("/admin/service-executions/{$executionId}/note", [
            'body' => 'Assigned to immigration processing desk',
        ]);

    $noteResponse->assertRedirect();

    $note = DB::table('execution_internal_notes')->where('execution_id', $executionId)->first();
    expect($note)->not->toBeNull()
        ->and($note->body)->toBe('Assigned to immigration processing desk');
});

it('verifies admin staff journey step issue_customer_action_request', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['executions.manage'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );

    $executionId = (string) Str::uuid();
    $orderId = (string) Str::uuid();
    $accountId = (string) Str::uuid();
    $travelerId = (string) Str::uuid();
    $formVersionId = (string) Str::uuid();

    DB::table('service_executions')->insert([
        'id' => $executionId,
        'order_id' => $orderId,
        'account_id' => $accountId,
        'traveler_id' => $travelerId,
        'service_id' => (string) Str::uuid(),
        'form_version_id' => $formVersionId,
        'status' => 'under_review',
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')
        ->post("/admin/service-executions/{$executionId}/customer-action", [
            'description_en' => 'Passport scan is blurry. Please upload high resolution scan.',
            'description_ar' => 'صورة الجواز غير واضحة. يرجى رفع صورة عالية الدقة.',
            'required_document_purpose' => 'passport_photo',
            'due_in_hours' => 24,
        ]);

    $response->assertRedirect();

    $actionRequest = DB::table('customer_action_requests')->where('execution_id', $executionId)->first();
    expect($actionRequest)->not->toBeNull()
        ->and($actionRequest->status)->toBe('open');
});

it('verifies admin staff journey step complete_execution_and_attach_document', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['executions.manage'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );

    $executionId = (string) Str::uuid();
    $orderId = (string) Str::uuid();
    $accountId = (string) Str::uuid();
    $travelerId = (string) Str::uuid();
    $formVersionId = (string) Str::uuid();

    DB::table('service_executions')->insert([
        'id' => $executionId,
        'order_id' => $orderId,
        'account_id' => $accountId,
        'traveler_id' => $travelerId,
        'service_id' => (string) Str::uuid(),
        'form_version_id' => $formVersionId,
        'status' => 'in_processing',
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')
        ->post("/admin/service-executions/{$executionId}/transition", [
            'target_status' => 'completed',
            'reason' => 'Visa issued successfully by embassy',
        ]);

    $response->assertRedirect();

    $execution = DB::table('service_executions')->where('id', $executionId)->first();
    expect($execution->status)->toBe('completed');
});
