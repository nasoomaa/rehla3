<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Tests\Support\StaffTestHelper;

it('approves top-up request and credits customer wallet via domain action', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['topups.review'],
        mfaConfirmedAt: CarbonImmutable::now()->subHour(),
    );

    // Seed customer and wallet
    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'TopUp Customer',
        email: 'customer_topup_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));

    $walletId = (string) Str::uuid();
    DB::table('wallets')->insert([
        'id' => $walletId,
        'account_id' => $customer->id,
        'currency' => 'SDG',
        'balance_minor' => 0,
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $bankAccountId = (string) Str::uuid();
    DB::table('company_bank_accounts')->insert([
        'id' => $bankAccountId,
        'bank_name_en' => 'Bank of Khartoum',
        'bank_name_ar' => 'بنك الخرطوم',
        'account_number' => '1234567890',
        'beneficiary_name' => 'Rehla Travel Agency',
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
        'amount_minor' => 5_000_000,
        'transaction_reference' => 'TX-APP-001',
        'normalized_reference' => 'TX-APP-001',
        'status' => 'under_review',
        'receipt_document_id' => (string) Str::uuid(),
        'submitted_at' => CarbonImmutable::now(),
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')
        ->post("/admin/top-up-requests/{$topUpId}/approve", [
            'notes' => 'Verified with bank receipt',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $updatedTopUp = DB::table('topup_requests')->where('id', $topUpId)->first();
    expect($updatedTopUp->status)->toBe('approved');

    $wallet = DB::table('wallets')->where('id', $walletId)->first();
    expect((int) $wallet->balance_minor)->toBe(5_000_000);
});

it('rejects top-up request with reason via domain action', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['topups.review'],
        mfaConfirmedAt: CarbonImmutable::now()->subHour(),
    );

    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'TopUp Customer 2',
        email: 'customer_topup2_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));

    $walletId = (string) Str::uuid();
    DB::table('wallets')->insert([
        'id' => $walletId,
        'account_id' => $customer->id,
        'currency' => 'SDG',
        'balance_minor' => 0,
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $bankAccountId = (string) Str::uuid();
    DB::table('company_bank_accounts')->insert([
        'id' => $bankAccountId,
        'bank_name_en' => 'Faisal Islamic Bank',
        'bank_name_ar' => 'بنك فيصل الإسلامي',
        'account_number' => '9876543210',
        'beneficiary_name' => 'Rehla Travel',
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
        'amount_minor' => 3_000_000,
        'transaction_reference' => 'TX-REJ-001',
        'normalized_reference' => 'TX-REJ-001',
        'status' => 'under_review',
        'receipt_document_id' => (string) Str::uuid(),
        'submitted_at' => CarbonImmutable::now(),
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')
        ->post("/admin/top-up-requests/{$topUpId}/reject", [
            'reason' => 'Receipt illegible and unverified',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $updatedTopUp = DB::table('topup_requests')->where('id', $topUpId)->first();
    expect($updatedTopUp->status)->toBe('rejected')
        ->and($updatedTopUp->rejection_reason)->toBe('Receipt illegible and unverified');
});

it('transitions execution state and records history via domain action', function (): void {
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
    $response = $this->actingAs($user, 'admin')
        ->post("/admin/service-executions/{$executionId}/transition", [
            'target_status' => 'under_review',
            'reason' => 'Starting initial review of documents',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $updatedExecution = DB::table('service_executions')->where('id', $executionId)->first();
    expect($updatedExecution->status)->toBe('under_review');
});

it('issues customer action request on an execution via domain action', function (): void {
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
            'description_en' => 'Please provide updated passport photo with clear background',
            'description_ar' => 'يرجى تقديم صورة جواز سفر محدثة بخلفية واضحة',
            'required_document_purpose' => 'passport_photo',
            'due_in_hours' => 48,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $requestRecord = DB::table('customer_action_requests')->where('execution_id', $executionId)->first();
    expect($requestRecord)->not->toBeNull()
        ->and($requestRecord->status)->toBe('open');

    // Execution should have transitioned to customer_action_required
    $updatedExecution = DB::table('service_executions')->where('id', $executionId)->first();
    expect($updatedExecution->status)->toBe('customer_action_required');
});
