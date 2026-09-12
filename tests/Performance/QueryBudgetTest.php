<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Tests\Support\StaffTestHelper;

beforeEach(function (): void {
    $this->now = CarbonImmutable::now();
});

it('enforces query budget on public and API service list (<= 20 queries)', function (): void {
    // Seed test services
    for ($i = 0; $i < 5; $i++) {
        DB::table('services')->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'slug' => 'perf-service-'.$i.'-'.Str::random(4),
            'name_en' => 'Perf Service '.$i,
            'name_ar' => 'خدمة '.$i,
            'short_description_en' => 'Short desc',
            'short_description_ar' => 'وصف قصير',
            'detailed_description_en' => 'Detailed desc',
            'detailed_description_ar' => 'وصف مفصل',
            'expected_duration_en' => '3 days',
            'expected_duration_ar' => '3 أيام',
            'current_price_minor' => 10000 + ($i * 1000),
            'currency' => 'SDG',
            'price_version' => 1,
            'status' => 'published',
            'sort_order' => $i,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->getJson('/api/v1/services');
    $response->assertOk();

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    expect($queryCount)->toBeLessThanOrEqual(20);
});

it('enforces query budget on order details view (<= 15 queries)', function (): void {
    $cust = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Perf Customer',
        email: 'perf_order_'.Str::random(8).'@example.com',
        password: 'Password123!',
    ));
    $user = Auth::guard('web')->getProvider()->retrieveById($cust->id);

    $walletId = (string) Str::uuid();
    DB::table('wallets')->insert([
        'id' => $walletId,
        'account_id' => $cust->id,
        'balance_minor' => 100000,
        'currency' => 'SDG',
        'created_at' => $this->now,
        'updated_at' => $this->now,
    ]);

    $ledgerId = (string) Str::uuid();
    $orderId = (string) Str::uuid();
    DB::table('ledger_entries')->insert([
        'id' => $ledgerId,
        'wallet_id' => $walletId,
        'type' => 'debit',
        'amount_minor' => 50000,
        'balance_after_minor' => 50000,
        'reference_type' => 'order_payment',
        'reference_id' => $orderId,
        'idempotency_key' => 'idem-perf-'.Str::random(8),
        'created_at' => $this->now,
    ]);

    $serviceId = (string) Str::uuid();
    DB::table('orders')->insert([
        'id' => $orderId,
        'account_id' => $cust->id,
        'service_id' => $serviceId,
        'traveler_id' => (string) Str::uuid(),
        'price_minor' => 50000,
        'amount_paid_minor' => 50000,
        'currency' => 'SDG',
        'debit_ledger_entry_id' => $ledgerId,
        'financial_status' => 'paid',
        'created_at' => $this->now,
    ]);

    DB::table('order_service_snapshots')->insert([
        'id' => (string) Str::uuid(),
        'order_id' => $orderId,
        'name_en' => 'Perf Service',
        'name_ar' => 'خدمة',
        'descriptions' => json_encode(['en' => 'Perf test']),
        'requirements' => json_encode([]),
        'expected_duration' => json_encode(['days' => 3]),
        'notes' => json_encode([]),
        'created_at' => $this->now,
    ]);

    DB::table('order_traveler_snapshots')->insert([
        'id' => (string) Str::uuid(),
        'order_id' => $orderId,
        'full_name' => 'Perf Traveler',
        'date_of_birth' => '1995-05-05',
        'gender' => 'male',
        'passport_number' => 'P998877',
        'passport_issued_at' => '2020-01-01',
        'passport_expires_at' => '2030-01-01',
        'created_at' => $this->now,
    ]);

    DB::table('order_form_snapshots')->insert([
        'id' => (string) Str::uuid(),
        'order_id' => $orderId,
        'form_version_id' => (string) Str::uuid(),
        'form_version' => 1,
        'form_checksum' => hash('sha256', 'schema'),
        'schema' => json_encode([]),
        'answers' => json_encode([]),
        'created_at' => $this->now,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($user, 'web')->get("/account/orders/{$orderId}");
    $response->assertOk();

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    expect($queryCount)->toBeLessThanOrEqual(15);
});

it('enforces query budget on admin overview (<= 20 queries)', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['reporting.view'],
        name: 'Perf Admin'
    );
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($user, 'admin')->get('/admin/overview');
    $response->assertOk();

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    expect($queryCount)->toBeLessThanOrEqual(20);
});

it('enforces strict pagination caps on API collection endpoints', function (): void {
    $response = $this->getJson('/api/v1/services?per_page=500');
    $response->assertOk();

    $data = $response->json('data');
    expect(count($data))->toBeLessThanOrEqual(100);
});
