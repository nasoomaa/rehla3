<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('proves zero data loss and trigger preservation across migration runs on immutable tables', function (): void {
    $now = CarbonImmutable::now();

    // 1. Prepare baseline records in immutable tables
    // Form Version
    $serviceId = (string) Str::uuid();
    $versionId = (string) Str::uuid();

    DB::table('form_versions')->insert([
        'id' => $versionId,
        'service_id' => $serviceId,
        'version' => 1,
        'schema' => json_encode(['fields' => [['name' => 'test_field', 'type' => 'text']]], JSON_THROW_ON_ERROR),
        'checksum' => hash('sha256', 'test_field'),
        'status' => 'published',
        'published_by' => (string) Str::uuid(),
        'published_at' => $now,
        'created_by' => (string) Str::uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Wallet & Ledger entry
    $walletId = (string) Str::uuid();
    $accountId = (string) Str::uuid();
    DB::table('wallets')->insert([
        'id' => $walletId,
        'account_id' => $accountId,
        'balance_minor' => 50000,
        'currency' => 'SDG',
        'lock_version' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $ledgerId = (string) Str::uuid();
    DB::table('ledger_entries')->insert([
        'id' => $ledgerId,
        'wallet_id' => $walletId,
        'type' => 'credit',
        'amount_minor' => 100000,
        'balance_after_minor' => 100000,
        'reference_type' => 'topup_request',
        'reference_id' => (string) Str::uuid(),
        'idempotency_key' => 'idem-upg-'.Str::random(10),
        'metadata' => json_encode(['reason' => 'topup_test'], JSON_THROW_ON_ERROR),
        'created_at' => $now,
    ]);

    // Audit entry
    $auditId = (string) Str::uuid();
    DB::table('audit_entries')->insert([
        'id' => $auditId,
        'actor_type' => 'system',
        'actor_id' => (string) Str::uuid(),
        'action' => 'upgrade.baseline.created',
        'subject_type' => 'system',
        'subject_id' => (string) Str::uuid(),
        'metadata' => json_encode(['baseline' => true], JSON_THROW_ON_ERROR),
        'ip_hash' => hash('sha256', '127.0.0.1'),
        'user_agent_hash' => hash('sha256', 'IntegrationTest/1.0'),
        'occurred_at' => $now,
    ]);

    // Order & Order Snapshot
    $orderId = (string) Str::uuid();
    $debitLedgerId = (string) Str::uuid();
    // Insert a debit ledger entry for the order foreign key
    DB::table('ledger_entries')->insert([
        'id' => $debitLedgerId,
        'wallet_id' => $walletId,
        'type' => 'debit',
        'amount_minor' => 50000,
        'balance_after_minor' => 50000,
        'reference_type' => 'order_payment',
        'reference_id' => $orderId,
        'idempotency_key' => 'idem-order-'.Str::random(10),
        'metadata' => json_encode(['purpose' => 'order_payment'], JSON_THROW_ON_ERROR),
        'created_at' => $now,
    ]);

    DB::table('orders')->insert([
        'id' => $orderId,
        'account_id' => $accountId,
        'service_id' => $serviceId,
        'traveler_id' => (string) Str::uuid(),
        'price_minor' => 50000,
        'amount_paid_minor' => 50000,
        'currency' => 'SDG',
        'debit_ledger_entry_id' => $debitLedgerId,
        'financial_status' => 'paid',
        'created_at' => $now,
    ]);

    $snapshotId = (string) Str::uuid();
    DB::table('order_service_snapshots')->insert([
        'id' => $snapshotId,
        'order_id' => $orderId,
        'name_en' => 'Upgrade Test Service',
        'name_ar' => 'خدمة اختبار الترقية',
        'descriptions' => json_encode(['en' => 'Test Desc', 'ar' => 'وصف'], JSON_THROW_ON_ERROR),
        'requirements' => json_encode([], JSON_THROW_ON_ERROR),
        'expected_duration' => json_encode(['days' => 5], JSON_THROW_ON_ERROR),
        'notes' => json_encode([], JSON_THROW_ON_ERROR),
        'created_at' => $now,
    ]);

    // Document record
    $documentId = (string) Str::uuid();
    $storageKey = 'documents/upgrade-test-'.Str::random(8).'.bin';
    DB::table('documents')->insert([
        'id' => $documentId,
        'owner_id' => $accountId,
        'purpose' => 'passport_scan',
        'disk' => 'private',
        'storage_key' => $storageKey,
        'original_name' => 'passport.pdf',
        'detected_mime' => 'application/pdf',
        'size_bytes' => 102400,
        'sha256' => hash('sha256', 'sample payload'),
        'status' => 'clean',
        'scanned_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // 2. Snapshot baseline state before running migrations
    $ledgerCountBefore = DB::table('ledger_entries')->count();
    $auditCountBefore = DB::table('audit_entries')->count();
    $formVersionCountBefore = DB::table('form_versions')->count();
    $orderSnapshotCountBefore = DB::table('order_service_snapshots')->count();
    $orderCountBefore = DB::table('orders')->count();
    $documentCountBefore = DB::table('documents')->count();

    // 3. Execute upgrade migration command
    $exitCode = Artisan::call('migrate', ['--force' => true]);
    expect($exitCode)->toBe(0);

    // 4. Assert exact row preservation after migration
    expect(DB::table('ledger_entries')->count())->toBe($ledgerCountBefore);
    expect(DB::table('audit_entries')->count())->toBe($auditCountBefore);
    expect(DB::table('form_versions')->count())->toBe($formVersionCountBefore);
    expect(DB::table('order_service_snapshots')->count())->toBe($orderSnapshotCountBefore);
    expect(DB::table('orders')->count())->toBe($orderCountBefore);
    expect(DB::table('documents')->count())->toBe($documentCountBefore);

    // Assert existing records remain intact and readable with original data
    $ledger = DB::table('ledger_entries')->where('id', $ledgerId)->first();
    expect($ledger)->not->toBeNull()
        ->and((int) $ledger->amount_minor)->toBe(100000)
        ->and($ledger->wallet_id)->toBe($walletId);

    $audit = DB::table('audit_entries')->where('id', $auditId)->first();
    expect($audit)->not->toBeNull()
        ->and($audit->action)->toBe('upgrade.baseline.created');

    $snapshot = DB::table('order_service_snapshots')->where('id', $snapshotId)->first();
    expect($snapshot)->not->toBeNull()
        ->and($snapshot->order_id)->toBe($orderId);

    $doc = DB::table('documents')->where('id', $documentId)->first();
    expect($doc)->not->toBeNull()
        ->and($doc->storage_key)->toBe($storageKey);

    // 5. Assert database immutability triggers remain active after migration
    expect(fn () => DB::table('ledger_entries')->where('id', $ledgerId)->update(['amount_minor' => 999999]))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('ledger_entries')->where('id', $ledgerId)->delete())
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('audit_entries')->where('id', $auditId)->update(['action' => 'tampered']))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('audit_entries')->where('id', $auditId)->delete())
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('form_versions')->where('id', $versionId)->update(['version' => 2]))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('form_versions')->where('id', $versionId)->delete())
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('orders')->where('id', $orderId)->update(['price_minor' => 999999]))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('orders')->where('id', $orderId)->delete())
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('order_service_snapshots')->where('id', $snapshotId)->update(['name_en' => 'tampered']))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('order_service_snapshots')->where('id', $snapshotId)->delete())
        ->toThrow(QueryException::class);
});
