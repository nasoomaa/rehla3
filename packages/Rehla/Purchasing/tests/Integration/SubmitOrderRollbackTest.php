<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Notifications\Actions\AppendOutboxMessage;
use Rehla\Notifications\Contracts\OutboxWriter;
use Rehla\Notifications\Data\OutboxMessageData;
use Rehla\Orders\Contracts\OrderWriter;
use Rehla\Orders\Data\CreatePaidOrderData;
use Rehla\Orders\Data\PaidOrderData;
use Rehla\Purchasing\Actions\SubmitOrder;
use Rehla\Purchasing\Data\SubmitOrderData;
use Rehla\Wallet\Contracts\WalletReader;
use Tests\Support\AssertsSafeTestingDatabase;

require_once __DIR__.'/SubmitOrderTest.php';

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
    Storage::fake('private');
});

it('rolls back all records if failure occurs after wallet debit', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    // Mock OrderWriter to throw an exception
    $failingOrderWriter = new class implements OrderWriter
    {
        public function createPaid(CreatePaidOrderData $data): PaidOrderData
        {
            throw new RuntimeException('Simulated database crash during order creation');
        }
    };

    app()->instance(OrderWriter::class, $failingOrderWriter);

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'rollback-after-debit-001',
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(RuntimeException::class, 'Simulated database crash during order creation');

    // Wallet balance must remain completely untouched
    $balance = app(WalletReader::class)->getBalance($customer['walletId']);
    expect($balance->minor)->toBe(100_000_00);

    // 0 ledger entries, 0 orders, 0 executions, 0 audit, 0 outbox
    expect(DB::table('ledger_entries')->where('reference_type', 'order')->count())->toBe(0)
        ->and(DB::table('orders')->count())->toBe(0)
        ->and(DB::table('order_service_snapshots')->count())->toBe(0)
        ->and(DB::table('service_executions')->count())->toBe(0)
        ->and(DB::table('audit_entries')->where('action', 'order.submitted')->count())->toBe(0)
        ->and(DB::table('outbox_messages')->where('event_name', 'order.submitted')->count())->toBe(0);

    // Attempt is not marked completed
    $attempt = DB::table('purchase_attempts')
        ->where('account_id', $customer['accountId'])
        ->where('idempotency_key', 'rollback-after-debit-001')
        ->first();
    expect($attempt?->status)->not->toBe('completed');
});

it('rolls back all records if failure occurs after execution creation', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    // Mock AuditWriter to throw an exception
    $failingAuditWriter = new class implements AuditWriter
    {
        public function append(AppendAuditData $data): string
        {
            throw new RuntimeException('Simulated crash during audit write');
        }
    };

    app()->instance(AuditWriter::class, $failingAuditWriter);

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'rollback-after-exec-001',
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(RuntimeException::class, 'Simulated crash during audit write');

    // Everything rolled back
    $balance = app(WalletReader::class)->getBalance($customer['walletId']);
    expect($balance->minor)->toBe(100_000_00);

    expect(DB::table('ledger_entries')->where('reference_type', 'order')->count())->toBe(0)
        ->and(DB::table('orders')->count())->toBe(0)
        ->and(DB::table('service_executions')->count())->toBe(0)
        ->and(DB::table('audit_entries')->where('action', 'order.submitted')->count())->toBe(0)
        ->and(DB::table('outbox_messages')->where('event_name', 'order.submitted')->count())->toBe(0);
});

it('rolls back all records if failure occurs during outbox write and permits retry', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    $failOutbox = true;

    $conditionalOutbox = new class($failOutbox) implements OutboxWriter
    {
        public function __construct(public bool &$shouldFail) {}

        public function append(OutboxMessageData $data): string
        {
            if ($this->shouldFail) {
                throw new RuntimeException('Simulated crash during outbox write');
            }

            return app(AppendOutboxMessage::class)->append($data);
        }
    };

    app()->instance(OutboxWriter::class, $conditionalOutbox);

    $idempotencyKey = 'retry-after-outbox-fail-001';

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: $idempotencyKey,
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    // First attempt fails
    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(RuntimeException::class, 'Simulated crash during outbox write');

    // Balance untouched
    $balance = app(WalletReader::class)->getBalance($customer['walletId']);
    expect($balance->minor)->toBe(100_000_00);
    expect(DB::table('orders')->count())->toBe(0);

    // Now fix the issue and retry with the SAME idempotency key
    $failOutbox = false;

    $result = app(SubmitOrder::class)->handle($data);

    expect($result->orderId)->not->toBeEmpty()
        ->and($result->executionId)->not->toBeEmpty();

    $balanceAfter = app(WalletReader::class)->getBalance($customer['walletId']);
    expect($balanceAfter->minor)->toBe(50_000_00);
    expect(DB::table('orders')->count())->toBe(1);
});
