<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Reporting\Data\MetricFilter;
use Rehla\Reporting\Queries\GetProductMetrics;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

function seedReportingFixture(): void
{
    // Clear relevant tables to ensure predictable metrics in this fixture
    DB::table('customer_action_responses')->delete();
    DB::table('execution_documents')->delete();
    DB::table('customer_action_requests')->delete();
    DB::table('execution_status_history')->delete();
    DB::table('execution_internal_notes')->delete();
    DB::table('service_executions')->delete();
    DB::table('order_form_snapshots')->delete();
    DB::table('order_traveler_snapshots')->delete();
    DB::table('order_service_snapshots')->delete();
    DB::table('orders')->delete();
    DB::table('topup_requests')->delete();
    DB::table('company_bank_accounts')->delete();
    DB::table('travelers')->delete();
    DB::table('users')->delete();
    DB::table('outbox_messages')->delete();

    // 1. Users: 4 in September 2026, 1 outside (August 2026)
    $user1 = (string) Str::uuid();
    $user2 = (string) Str::uuid();
    $user3 = (string) Str::uuid();
    $user4 = (string) Str::uuid();
    $userOut = (string) Str::uuid();

    DB::table('users')->insert([
        ['id' => $user1, 'name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'secret', 'created_at' => '2026-09-02 10:00:00+00', 'updated_at' => '2026-09-02 10:00:00+00'],
        ['id' => $user2, 'name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'secret', 'created_at' => '2026-09-05 12:00:00+00', 'updated_at' => '2026-09-05 12:00:00+00'],
        ['id' => $user3, 'name' => 'User 3', 'email' => 'user3@example.com', 'password' => 'secret', 'created_at' => '2026-09-10 14:00:00+00', 'updated_at' => '2026-09-10 14:00:00+00'],
        ['id' => $user4, 'name' => 'User 4', 'email' => 'user4@example.com', 'password' => 'secret', 'created_at' => '2026-09-15 16:00:00+00', 'updated_at' => '2026-09-15 16:00:00+00'],
        ['id' => $userOut, 'name' => 'User Out', 'email' => 'userout@example.com', 'password' => 'secret', 'created_at' => '2026-08-15 10:00:00+00', 'updated_at' => '2026-08-15 10:00:00+00'],
    ]);

    // 2. Travelers: 6 in September 2026, 1 outside (August 2026)
    $travelerIds = [];
    for ($i = 1; $i <= 6; $i++) {
        $tid = (string) Str::uuid();
        $travelerIds[] = $tid;
        DB::table('travelers')->insert([
            'id' => $tid,
            'owner_id' => $i <= 2 ? $user1 : ($i <= 4 ? $user2 : $user3),
            'full_name' => "Traveler {$i}",
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'passport_number' => "P100000{$i}",
            'normalized_passport_number' => "P100000{$i}",
            'passport_issued_at' => '2020-01-01',
            'passport_expires_at' => '2030-01-01',
            'created_at' => "2026-09-0{$i} 08:00:00+00",
            'updated_at' => "2026-09-0{$i} 08:00:00+00",
        ]);
    }
    // Outside period traveler
    DB::table('travelers')->insert([
        'id' => (string) Str::uuid(),
        'owner_id' => $userOut,
        'full_name' => 'Traveler August',
        'date_of_birth' => '1990-01-01',
        'gender' => 'female',
        'passport_number' => 'P9999999',
        'normalized_passport_number' => 'P9999999',
        'passport_issued_at' => '2020-01-01',
        'passport_expires_at' => '2030-01-01',
        'created_at' => '2026-08-20 08:00:00+00',
        'updated_at' => '2026-08-20 08:00:00+00',
    ]);

    // 3. Bank Accounts
    $bank1 = (string) Str::uuid();
    $bank2 = (string) Str::uuid();
    DB::table('company_bank_accounts')->insert([
        ['id' => $bank1, 'bank_name_en' => 'Bank of Khartoum', 'bank_name_ar' => 'بنك الخرطوم', 'beneficiary_name' => 'Rehla', 'account_number' => '1111', 'active' => true, 'sort_order' => 1, 'created_at' => '2026-09-01 00:00:00+00'],
        ['id' => $bank2, 'bank_name_en' => 'Faisal Islamic Bank', 'bank_name_ar' => 'بنك فيصل الإسلامي', 'beneficiary_name' => 'Rehla', 'account_number' => '2222', 'active' => true, 'sort_order' => 2, 'created_at' => '2026-09-01 00:00:00+00'],
    ]);

    // 4. Top-ups: 4 submitted in September (2 approved, 1 rejected, 1 under review), 1 outside
    // TopUp 1: 100_000_00 approved in 1800s
    DB::table('topup_requests')->insert([
        'id' => (string) Str::uuid(),
        'account_id' => $user1,
        'wallet_id' => (string) Str::uuid(),
        'bank_account_id' => $bank1,
        'amount_minor' => 100_000_00,
        'transaction_reference' => 'TX1',
        'normalized_reference' => 'TX1',
        'receipt_document_id' => (string) Str::uuid(),
        'status' => 'approved',
        'submitted_at' => '2026-09-05 10:00:00+00',
        'decided_at' => '2026-09-05 10:30:00+00', // 1800 seconds
        'reviewed_by' => (string) Str::uuid(),
        'created_at' => '2026-09-05 10:00:00+00',
    ]);

    // TopUp 2: 50_000_00 approved in 600s
    DB::table('topup_requests')->insert([
        'id' => (string) Str::uuid(),
        'account_id' => $user2,
        'wallet_id' => (string) Str::uuid(),
        'bank_account_id' => $bank2,
        'amount_minor' => 50_000_00,
        'transaction_reference' => 'TX2',
        'normalized_reference' => 'TX2',
        'receipt_document_id' => (string) Str::uuid(),
        'status' => 'approved',
        'submitted_at' => '2026-09-06 11:00:00+00',
        'decided_at' => '2026-09-06 11:10:00+00', // 600 seconds
        'reviewed_by' => (string) Str::uuid(),
        'created_at' => '2026-09-06 11:00:00+00',
    ]);

    // TopUp 3: 20_000_00 rejected in 1200s
    DB::table('topup_requests')->insert([
        'id' => (string) Str::uuid(),
        'account_id' => $user3,
        'wallet_id' => (string) Str::uuid(),
        'bank_account_id' => $bank1,
        'amount_minor' => 20_000_00,
        'transaction_reference' => 'TX3',
        'normalized_reference' => 'TX3',
        'receipt_document_id' => (string) Str::uuid(),
        'status' => 'rejected',
        'rejection_reason' => 'Receipt blurred',
        'submitted_at' => '2026-09-07 12:00:00+00',
        'decided_at' => '2026-09-07 12:20:00+00', // 1200 seconds
        'reviewed_by' => (string) Str::uuid(),
        'created_at' => '2026-09-07 12:00:00+00',
    ]);

    // TopUp 4: 30_000_00 pending
    DB::table('topup_requests')->insert([
        'id' => (string) Str::uuid(),
        'account_id' => $user4,
        'wallet_id' => (string) Str::uuid(),
        'bank_account_id' => $bank1,
        'amount_minor' => 30_000_00,
        'transaction_reference' => 'TX4',
        'normalized_reference' => 'TX4',
        'receipt_document_id' => (string) Str::uuid(),
        'status' => 'under_review',
        'submitted_at' => '2026-09-08 14:00:00+00',
        'created_at' => '2026-09-08 14:00:00+00',
    ]);

    // TopUp outside period
    DB::table('topup_requests')->insert([
        'id' => (string) Str::uuid(),
        'account_id' => $userOut,
        'wallet_id' => (string) Str::uuid(),
        'bank_account_id' => $bank1,
        'amount_minor' => 80_000_00,
        'transaction_reference' => 'TX-OUT',
        'normalized_reference' => 'TX-OUT',
        'receipt_document_id' => (string) Str::uuid(),
        'status' => 'approved',
        'submitted_at' => '2026-08-25 10:00:00+00',
        'decided_at' => '2026-08-25 10:15:00+00',
        'created_at' => '2026-08-25 10:00:00+00',
    ]);

    // 5. Orders & Snapshots: 2 in September (sum = 10_000_00), 1 outside
    $order1 = (string) Str::uuid();
    $order2 = (string) Str::uuid();
    $service1 = (string) Str::uuid();
    $service2 = (string) Str::uuid();

    DB::table('orders')->insert([
        [
            'id' => $order1,
            'account_id' => $user1,
            'service_id' => $service1,
            'traveler_id' => $travelerIds[0],
            'price_minor' => 6_000_00,
            'amount_paid_minor' => 6_000_00,
            'currency' => 'SDG',
            'financial_status' => 'paid',
            'debit_ledger_entry_id' => (string) Str::uuid(),
            'created_at' => '2026-09-10 10:00:00+00',
        ],
        [
            'id' => $order2,
            'account_id' => $user2,
            'service_id' => $service2,
            'traveler_id' => $travelerIds[1],
            'price_minor' => 4_000_00,
            'amount_paid_minor' => 4_000_00,
            'currency' => 'SDG',
            'financial_status' => 'paid',
            'debit_ledger_entry_id' => (string) Str::uuid(),
            'created_at' => '2026-09-12 12:00:00+00',
        ],
        [
            'id' => (string) Str::uuid(),
            'account_id' => $userOut,
            'service_id' => $service1,
            'traveler_id' => (string) Str::uuid(),
            'price_minor' => 5_000_00,
            'amount_paid_minor' => 5_000_00,
            'currency' => 'SDG',
            'financial_status' => 'paid',
            'debit_ledger_entry_id' => (string) Str::uuid(),
            'created_at' => '2026-08-28 12:00:00+00',
        ],
    ]);

    DB::table('order_service_snapshots')->insert([
        [
            'id' => (string) Str::uuid(),
            'order_id' => $order1,
            'name_en' => 'Umrah Visa',
            'name_ar' => 'تأشيرة العمرة',
            'descriptions' => json_encode(['summary' => 'Visa']),
            'requirements' => json_encode([]),
            'expected_duration' => json_encode([]),
            'notes' => json_encode([]),
            'created_at' => '2026-09-10 10:00:00+00',
        ],
        [
            'id' => (string) Str::uuid(),
            'order_id' => $order2,
            'name_en' => 'Transit Visa',
            'name_ar' => 'تأشيرة المرور',
            'descriptions' => json_encode(['summary' => 'Transit']),
            'requirements' => json_encode([]),
            'expected_duration' => json_encode([]),
            'notes' => json_encode([]),
            'created_at' => '2026-09-12 12:00:00+00',
        ],
    ]);

    // 6. Service Executions: 2 in September (1 completed, 1 in_processing)
    $exec1 = (string) Str::uuid();
    $exec2 = (string) Str::uuid();

    DB::table('service_executions')->insert([
        [
            'id' => $exec1,
            'order_id' => $order1,
            'account_id' => $user1,
            'traveler_id' => $travelerIds[0],
            'service_id' => $service1,
            'form_version_id' => (string) Str::uuid(),
            'status' => 'completed',

            'created_at' => '2026-09-10 10:00:00+00',
            'last_status_at' => '2026-09-11 10:00:00+00', // 86400 seconds
            'updated_at' => '2026-09-11 10:00:00+00',
        ],
        [
            'id' => $exec2,
            'order_id' => $order2,
            'account_id' => $user2,
            'traveler_id' => $travelerIds[1],
            'service_id' => $service2,
            'form_version_id' => (string) Str::uuid(),
            'status' => 'in_processing',

            'created_at' => '2026-09-12 12:00:00+00',
            'last_status_at' => '2026-09-12 14:00:00+00',
            'updated_at' => '2026-09-12 14:00:00+00',
        ],
    ]);

    // 7. Customer Action Requests: 1 created in September
    DB::table('customer_action_requests')->insert([
        'id' => (string) Str::uuid(),
        'execution_id' => $exec2,
        'requested_by' => (string) Str::uuid(),
        'description_en' => 'Please upload photo',
        'description_ar' => 'يرجى رفع الصورة',
        'required_document_purpose' => 'passport',
        'status' => 'open',
        'created_at' => '2026-09-13 09:00:00+00',
    ]);

    // 8. Outbox messages: 2 in September
    DB::table('outbox_messages')->insert([
        [
            'id' => (string) Str::uuid(),
            'event_name' => 'order.submitted',
            'aggregate_type' => 'order',
            'aggregate_id' => $order1,
            'payload_version' => 1,
            'payload' => json_encode(['order_id' => $order1]),
            'deduplication_key' => 'dedup-1',
            'created_at' => '2026-09-10 10:00:00+00',
            'available_at' => '2026-09-10 10:00:00+00',
            'delivered_at' => '2026-09-10 10:00:05+00', // lag = 5 seconds
        ],
        [
            'id' => (string) Str::uuid(),
            'event_name' => 'order.submitted',
            'aggregate_type' => 'order',
            'aggregate_id' => $order2,
            'payload_version' => 1,
            'payload' => json_encode(['order_id' => $order2]),
            'deduplication_key' => 'dedup-2',
            'created_at' => '2026-09-12 12:00:00+00',
            'available_at' => '2026-09-12 12:00:00+00',
            'delivered_at' => '2026-09-12 12:00:15+00', // lag = 15 seconds
        ],
    ]);
}

function period(string $from, string $to): MetricFilter
{
    return new MetricFilter(
        fromUtc: CarbonImmutable::parse($from, 'UTC')->startOfDay(),
        toUtc: CarbonImmutable::parse($to, 'UTC')->startOfDay(),
        displayTimezone: 'Africa/Khartoum',
    );
}

it('calculates the twelve phase-one metrics from one fixed fixture', function (): void {
    seedReportingFixture();
    $filter = period('2026-09-01', '2026-10-01');
    $metrics = app(GetProductMetrics::class)->handle($filter);

    expect($metrics->registeredUsers)->toBe(4)
        ->and($metrics->savedTravelers)->toBe(6)
        ->and($metrics->orderVolumeMinor)->toBe(10_000_00)
        ->and($metrics->topUpCompletionRate)->toBe(75.0)
        ->and($metrics->topUpApprovalRatio)->toBe(2.0)
        ->and($metrics->completedOrderPercentage)->toBe(50.0);

    // Verify additional Phase 1 metrics
    expect($metrics->totalOrders)->toBe(2)
        ->and($metrics->grossRevenueSdg)->toBe(10_000_00)
        ->and($metrics->topUpVolumeMinor)->toBe(150_000_00)
        ->and($metrics->topUpRejectionRate)->toBe(25.0)
        ->and($metrics->averageReviewSeconds)->toBe(1200.0) // (1800 + 600 + 1200) / 3
        ->and($metrics->averageFulfillmentSeconds)->toBe(86400.0)
        ->and($metrics->customerActionVolume)->toBe(1)
        ->and($metrics->actionRequestRate)->toBe(50.0) // 1 out of 2 executions
        ->and($metrics->activeCustomersCount)->toBe(2)
        ->and($metrics->repeatCustomerRate)->toBe(0.0)
        ->and($metrics->travelerReuseRate)->toBe(0.0)
        ->and($metrics->systemOutboxLagSeconds)->toBe(15.0);

    // Verify grouped breakdowns
    expect($metrics->ordersByService)->toHaveCount(2);
    expect($metrics->bankTopUpChannelShare)->toHaveCount(2);
});

it('handles empty periods with zero and null denominators cleanly', function (): void {
    seedReportingFixture();
    // Period with no activity: year 2027
    $filter = period('2027-01-01', '2027-02-01');
    $metrics = app(GetProductMetrics::class)->handle($filter);

    expect($metrics->registeredUsers)->toBe(0)
        ->and($metrics->savedTravelers)->toBe(0)
        ->and($metrics->orderVolumeMinor)->toBe(0)
        ->and($metrics->totalOrders)->toBe(0)
        ->and($metrics->topUpCompletionRate)->toBeNull()
        ->and($metrics->topUpApprovalRatio)->toBeNull()
        ->and($metrics->topUpRejectionRate)->toBeNull()
        ->and($metrics->averageReviewSeconds)->toBeNull()
        ->and($metrics->completedOrderPercentage)->toBeNull()
        ->and($metrics->averageFulfillmentSeconds)->toBeNull()
        ->and($metrics->customerActionVolume)->toBe(0)
        ->and($metrics->actionRequestRate)->toBeNull()
        ->and($metrics->repeatCustomerRate)->toBeNull()
        ->and($metrics->travelerReuseRate)->toBeNull()
        ->and($metrics->activeCustomersCount)->toBe(0)
        ->and($metrics->systemOutboxLagSeconds)->toBeNull();
});

it('measures repeat customer rate and traveler reuse rate when repeat purchases occur', function (): void {
    seedReportingFixture();

    // Add a second order for user1 with the same traveler
    $user1 = DB::table('users')->where('email', 'user1@example.com')->value('id');
    $traveler1 = DB::table('travelers')->where('owner_id', $user1)->value('id');
    $service1 = DB::table('orders')->where('account_id', $user1)->value('service_id');

    DB::table('orders')->insert([
        'id' => (string) Str::uuid(),
        'account_id' => $user1,
        'service_id' => $service1,
        'traveler_id' => $traveler1,
        'price_minor' => 3_000_00,
        'amount_paid_minor' => 3_000_00,
        'currency' => 'SDG',
        'financial_status' => 'paid',
        'debit_ledger_entry_id' => (string) Str::uuid(),
        'created_at' => '2026-09-18 10:00:00+00',
    ]);

    $metrics = app(GetProductMetrics::class)->handle(period('2026-09-01', '2026-10-01'));

    // Now: 2 active accounts, 1 of them has >1 order => 1/2 = 50.0%
    expect($metrics->repeatCustomerRate)->toBe(50.0);
    // 2 distinct travelers used, 1 traveler used >1 time => 1/2 = 50.0%
    expect($metrics->travelerReuseRate)->toBe(50.0);
});
