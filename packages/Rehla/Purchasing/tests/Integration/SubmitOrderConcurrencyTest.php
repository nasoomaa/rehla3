<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rehla\Purchasing\Actions\SubmitOrder;
use Rehla\Purchasing\Data\SubmitOrderData;
use Rehla\Wallet\Contracts\WalletReader;
use Rehla\Wallet\Exceptions\InsufficientBalanceException;
use Tests\Support\AssertsSafeTestingDatabase;

require_once __DIR__.'/SubmitOrderTest.php';

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
    Storage::fake('private');
});

it('allows only one order to succeed when two orders compete for balance sufficient for only one', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    // Customer has only 50_000_00 (can only afford one order)
    $customer = setupCustomerWithTravelerAndWallet(50_000_00);

    $data1 = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'concurrent-key-1',
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    $data2 = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'concurrent-key-2',
        answers: [
            'emergency_contact' => '+249922222222',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    $action = app(SubmitOrder::class);

    $successCount = 0;
    $insufficientCount = 0;

    try {
        $action->handle($data1);
        $successCount++;
    } catch (InsufficientBalanceException) {
        $insufficientCount++;
    }

    try {
        $action->handle($data2);
        $successCount++;
    } catch (InsufficientBalanceException) {
        $insufficientCount++;
    }

    expect($successCount)->toBe(1)
        ->and($insufficientCount)->toBe(1);

    // Final balance is exactly 0, never negative
    $balance = app(WalletReader::class)->getBalance($customer['walletId']);
    expect($balance->minor)->toBe(0)
        ->and($balance->minor)->toBeGreaterThanOrEqual(0);

    // Only 1 order exists in database
    expect(DB::table('orders')->where('account_id', $customer['accountId'])->count())->toBe(1);
});

it('handles replay calls with identical results and single debit entry', function (): void {
    $setup = createTestServiceAndForm(40_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    $idempotencyKey = 'concurrent-replay-'.Str::random(8);

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 40_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: $idempotencyKey,
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    $action = app(SubmitOrder::class);

    $res1 = $action->handle($data);
    $res2 = $action->handle($data);

    expect($res1->orderId)->toBe($res2->orderId)
        ->and($res1->executionId)->toBe($res2->executionId);

    $debits = DB::table('ledger_entries')
        ->where('reference_type', 'order')
        ->where('reference_id', $res1->orderId)
        ->count();
    expect($debits)->toBe(1);

    $balance = app(WalletReader::class)->getBalance($customer['walletId']);
    expect($balance->minor)->toBe(60_000_00);
});
