<?php

declare(strict_types=1);

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Rehla\Purchasing\Enums\PurchaseAttemptStatus;
use Rehla\Purchasing\Models\PurchaseAttempt;
use Rehla\Purchasing\Support\CanonicalPurchaseFingerprint;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('allows different accounts to use the same idempotency key without collision', function (): void {
    $accountA = (string) Str::uuid();
    $accountB = (string) Str::uuid();
    $idempotencyKey = 'req-12345';
    $fingerprint = CanonicalPurchaseFingerprint::from(['service' => 'srv-1']);

    $attemptA = PurchaseAttempt::create([
        'id' => (string) Str::uuid(),
        'account_id' => $accountA,
        'idempotency_key' => $idempotencyKey,
        'request_fingerprint' => $fingerprint,
        'status' => PurchaseAttemptStatus::InProgress,
    ]);

    $attemptB = PurchaseAttempt::create([
        'id' => (string) Str::uuid(),
        'account_id' => $accountB,
        'idempotency_key' => $idempotencyKey,
        'request_fingerprint' => $fingerprint,
        'status' => PurchaseAttemptStatus::InProgress,
    ]);

    expect($attemptA->id)->not->toBe($attemptB->id)
        ->and($attemptA->account_id)->toBe($accountA)
        ->and($attemptB->account_id)->toBe($accountB);
});

it('prevents duplicate idempotency keys for the same account via unique constraint', function (): void {
    $accountId = (string) Str::uuid();
    $idempotencyKey = 'unique-key-per-account';
    $fingerprint = CanonicalPurchaseFingerprint::from(['service' => 'srv-1']);

    PurchaseAttempt::create([
        'id' => (string) Str::uuid(),
        'account_id' => $accountId,
        'idempotency_key' => $idempotencyKey,
        'request_fingerprint' => $fingerprint,
        'status' => PurchaseAttemptStatus::InProgress,
    ]);

    expect(fn () => PurchaseAttempt::create([
        'id' => (string) Str::uuid(),
        'account_id' => $accountId,
        'idempotency_key' => $idempotencyKey,
        'request_fingerprint' => $fingerprint,
        'status' => PurchaseAttemptStatus::InProgress,
    ]))->toThrow(UniqueConstraintViolationException::class);
});
