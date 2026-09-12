<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\DeactivateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Documents\Actions\BeginUpload;
use Rehla\Documents\Actions\ScanDocument;
use Rehla\Documents\Actions\StoreUpload;
use Rehla\Documents\Data\BeginUploadData;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\Documents\Exceptions\DocumentAccessDenied;
use Rehla\Documents\Exceptions\DocumentNotClean;
use Rehla\Forms\Actions\CreateFormDraft;
use Rehla\Forms\Actions\PublishFormVersion;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Enums\FieldType;
use Rehla\Forms\Exceptions\FormValidationFailedException;
use Rehla\Purchasing\Actions\SubmitOrder;
use Rehla\Purchasing\Data\SubmitOrderData;
use Rehla\Purchasing\Exceptions\FormVersionChangedException;
use Rehla\Purchasing\Exceptions\IdempotencyKeyReusedException;
use Rehla\Purchasing\Exceptions\PriceChangedException;
use Rehla\Purchasing\Exceptions\ServiceNotAvailableException;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;
use Rehla\Travelers\Exceptions\TravelerNotFound;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Wallet\Contracts\WalletCreditor;
use Rehla\Wallet\Contracts\WalletReader;
use Rehla\Wallet\Data\CreditWalletData;
use Rehla\Wallet\Exceptions\InsufficientBalanceException;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
    Storage::fake('private');
});

function createTestDocument(string $ownerId, DocumentPurpose $purpose = DocumentPurpose::Passport): string
{
    $session = app(BeginUpload::class)->handle(new BeginUploadData(
        ownerId: $ownerId,
        purpose: $purpose,
    ));

    $validPdfContent = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('doc.pdf', $validPdfContent);
    $doc = app(StoreUpload::class)->handle($session->id, $file);
    $scanned = app(ScanDocument::class)->handle($doc->id);

    return $scanned->id;
}

function createTestServiceAndForm(int $priceMinor = 50_000_00): array
{
    $createService = app(CreateService::class);
    $publishService = app(PublishService::class);

    $service = $createService->execute(new CreateServiceData(
        slug: 'test-service-'.Str::random(6),
        nameEn: 'Umrah Visa Service',
        nameAr: 'تأشيرة العمرة',
        shortDescriptionEn: 'Fast visa processing',
        shortDescriptionAr: 'معالجة سريعة للتأشيرة',
        detailedDescriptionEn: 'Full support for Umrah visa issuance.',
        detailedDescriptionAr: 'دعم كامل لإصدار تأشيرة العمرة.',
        expectedDurationEn: '3-5 business days',
        expectedDurationAr: '٣-٥ أيام عمل',
        priceMinor: $priceMinor,
        requirements: [
            ['text_en' => 'Valid passport', 'text_ar' => 'جواز سفر ساري', 'sort_order' => 1],
        ],
        media: [
            ['document_id' => (string) Str::uuid(), 'alt_en' => 'Banner', 'alt_ar' => 'بانر', 'sort_order' => 1],
        ],
    ), actorId: (string) Str::uuid());

    $publishService->execute($service->id, actorId: (string) Str::uuid());

    $createDraft = app(CreateFormDraft::class);
    $publishVersion = app(PublishFormVersion::class);

    $fields = [
        new FormFieldData(
            key: 'emergency_contact',
            type: FieldType::ShortText,
            labelEn: 'Emergency Contact',
            labelAr: 'رقم الطوارئ',
            order: 1,
            required: true,
        ),
        new FormFieldData(
            key: 'passport_doc',
            type: FieldType::File,
            labelEn: 'Passport Document',
            labelAr: 'مستند الجواز',
            order: 2,
            required: true,
        ),
    ];

    $draft = $createDraft->execute($service->id, $fields, (string) Str::uuid());
    $publishedForm = $publishVersion->execute($draft->id, (string) Str::uuid());

    return [
        'serviceId' => $service->id,
        'priceMinor' => $priceMinor,
        'priceVersion' => 1,
        'formVersionId' => $publishedForm->id,
    ];
}

function setupCustomerWithTravelerAndWallet(int $initialBalanceMinor = 100_000_00): array
{
    $accountId = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($accountId);

    if ($initialBalanceMinor > 0) {
        app(WalletCreditor::class)->credit(new CreditWalletData(
            walletId: $wallet->id,
            amountMinor: $initialBalanceMinor,
            referenceType: 'initial_deposit',
            referenceId: (string) Str::uuid(),
            idempotencyKey: 'credit-'.Str::random(12),
        ));
    }

    $traveler = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $accountId,
        fullName: 'Ahmed Hassan',
        dateOfBirth: '1988-04-12',
        gender: Gender::Male,
        passportNumber: 'P'.rand(1000000, 9999999),
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));

    $docId = createTestDocument($accountId, DocumentPurpose::Passport);

    return [
        'accountId' => $accountId,
        'walletId' => $wallet->id,
        'travelerId' => $traveler->id,
        'documentId' => $docId,
    ];
}

it('creates one debit, order, execution, audit, and outbox record on valid submission', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    $idempotencyKey = 'order-test-001';

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: $idempotencyKey,
        answers: [
            'emergency_contact' => '+249912345678',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    $action = app(SubmitOrder::class);
    $result = $action->handle($data);

    expect($result->orderId)->not->toBeEmpty()
        ->and($result->executionId)->not->toBeEmpty()
        ->and($result->status)->toBe('order_received')
        ->and($result->amountPaidMinor)->toBe(50_000_00)
        ->and($result->currency)->toBe('SDG')
        ->and($result->metadata['is_replay'])->toBeFalse();

    // 1 debit ledger entry
    $debits = DB::table('ledger_entries')
        ->where('reference_type', 'order')
        ->where('reference_id', $result->orderId)
        ->get();
    expect($debits)->toHaveCount(1)
        ->and($debits->first()->type)->toBe('debit')
        ->and($debits->first()->amount_minor)->toBe(50_000_00);

    // Wallet balance updated
    $walletBalance = app(WalletReader::class)->getBalance($customer['walletId']);
    expect($walletBalance->minor)->toBe(50_000_00);

    // 1 order with snapshots
    $order = DB::table('orders')->where('id', $result->orderId)->first();
    expect($order)->not->toBeNull()
        ->and($order->financial_status)->toBe('paid');

    expect(DB::table('order_service_snapshots')->where('order_id', $result->orderId)->count())->toBe(1)
        ->and(DB::table('order_traveler_snapshots')->where('order_id', $result->orderId)->count())->toBe(1)
        ->and(DB::table('order_form_snapshots')->where('order_id', $result->orderId)->count())->toBe(1);

    // 1 execution with history
    $execution = DB::table('service_executions')->where('id', $result->executionId)->first();
    expect($execution)->not->toBeNull()
        ->and($execution->order_id)->toBe($result->orderId)
        ->and($execution->status)->toBe('order_received');

    expect(DB::table('execution_status_history')->where('execution_id', $result->executionId)->count())->toBe(1);

    // 1 audit entry for order.submitted
    $auditEntries = DB::table('audit_entries')
        ->where('subject_type', 'order')
        ->where('subject_id', $result->orderId)
        ->where('action', 'order.submitted')
        ->get();
    expect($auditEntries)->toHaveCount(1);

    // 1 outbox message for order.submitted
    $outboxMessages = DB::table('outbox_messages')
        ->where('aggregate_type', 'order')
        ->where('aggregate_id', $result->orderId)
        ->where('event_name', 'order.submitted')
        ->get();
    expect($outboxMessages)->toHaveCount(1);

    // Purchase attempt marked completed
    $attempt = DB::table('purchase_attempts')
        ->where('account_id', $customer['accountId'])
        ->where('idempotency_key', $idempotencyKey)
        ->first();
    expect($attempt)->not->toBeNull()
        ->and($attempt->status)->toBe('completed')
        ->and($attempt->order_id)->toBe($result->orderId);
});

it('replays identical response for same idempotency key and fingerprint without re-debiting', function (): void {
    $setup = createTestServiceAndForm(30_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    $idempotencyKey = 'replay-test-001';

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 30_000_00,
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
    $result1 = $action->handle($data);
    $result2 = $action->handle($data);

    expect($result2->orderId)->toBe($result1->orderId)
        ->and($result2->executionId)->toBe($result1->executionId)
        ->and($result2->metadata['is_replay'])->toBeTrue();

    // Still only 1 debit in the ledger
    $debits = DB::table('ledger_entries')
        ->where('reference_type', 'order')
        ->where('reference_id', $result1->orderId)
        ->count();
    expect($debits)->toBe(1);

    // Balance remains 70_000_00
    $walletBalance = app(WalletReader::class)->getBalance($customer['walletId']);
    expect($walletBalance->minor)->toBe(70_000_00);
});

it('rejects reused idempotency key with different payload', function (): void {
    $setup = createTestServiceAndForm(20_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    $idempotencyKey = 'conflict-test-001';

    $data1 = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 20_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: $idempotencyKey,
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
        acceptedPriceMinor: 20_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: $idempotencyKey,
        answers: [
            'emergency_contact' => '+249922222222', // different answer
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    $action = app(SubmitOrder::class);
    $action->handle($data1);

    expect(fn () => $action->handle($data2))
        ->toThrow(IdempotencyKeyReusedException::class);
});

it('rejects order if service is deactivated without creating records', function (): void {
    $setup = createTestServiceAndForm(25_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    // Deactivate service
    app(DeactivateService::class)->execute($setup['serviceId'], actorId: (string) Str::uuid());

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 25_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'deactivated-service-001',
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(ServiceNotAvailableException::class);

    expect(DB::table('orders')->count())->toBe(0)
        ->and(DB::table('service_executions')->count())->toBe(0)
        ->and(DB::table('ledger_entries')->where('reference_type', 'order')->count())->toBe(0);
});

it('rejects order if service price or version changed', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 40_000_00, // Price mismatch: current is 50_000_00
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'price-mismatch-001',
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(PriceChangedException::class);

    expect(DB::table('orders')->count())->toBe(0)
        ->and(DB::table('ledger_entries')->where('reference_type', 'order')->count())->toBe(0);
});

it('rejects order if form version is outdated or mismatched', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: (string) Str::uuid(), // Invalid form version
        idempotencyKey: 'form-mismatch-001',
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(FormVersionChangedException::class);

    expect(DB::table('orders')->count())->toBe(0);
});

it('rejects order if traveler does not belong to account', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customerA = setupCustomerWithTravelerAndWallet(100_000_00);
    $customerB = setupCustomerWithTravelerAndWallet(100_000_00);

    $data = new SubmitOrderData(
        accountId: $customerA['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customerB['travelerId'], // Belongs to customer B
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'unowned-traveler-001',
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customerA['documentId'],
        ],
        documentIds: [$customerA['documentId']],
    );

    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(TravelerNotFound::class);

    expect(DB::table('orders')->count())->toBe(0);
});

it('rejects order if required form answers are missing', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'missing-answers-001',
        answers: [
            // emergency_contact is missing
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(FormValidationFailedException::class);

    expect(DB::table('orders')->count())->toBe(0);
});

it('rejects order if document is not owned by account', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customerA = setupCustomerWithTravelerAndWallet(100_000_00);
    $customerB = setupCustomerWithTravelerAndWallet(100_000_00);

    $data = new SubmitOrderData(
        accountId: $customerA['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customerA['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'unowned-doc-001',
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customerB['documentId'], // Document belongs to customer B
        ],
        documentIds: [$customerB['documentId']],
    );

    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(DocumentAccessDenied::class);

    expect(DB::table('orders')->count())->toBe(0);
});

it('rejects order if document is not clean', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customer = setupCustomerWithTravelerAndWallet(100_000_00);

    // Force document status to pending_scan
    DB::table('documents')->where('id', $customer['documentId'])->update(['status' => 'pending_scan']);

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'unclean-doc-001',
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(DocumentNotClean::class);

    expect(DB::table('orders')->count())->toBe(0);
});

it('rejects order if wallet balance is insufficient', function (): void {
    $setup = createTestServiceAndForm(50_000_00);
    $customer = setupCustomerWithTravelerAndWallet(10_000_00); // Only 10_000_00, requires 50_000_00

    $data = new SubmitOrderData(
        accountId: $customer['accountId'],
        serviceId: $setup['serviceId'],
        travelerId: $customer['travelerId'],
        acceptedPriceMinor: 50_000_00,
        acceptedPriceVersion: 1,
        formVersionId: $setup['formVersionId'],
        idempotencyKey: 'insufficient-balance-001',
        answers: [
            'emergency_contact' => '+249911111111',
            'passport_doc' => $customer['documentId'],
        ],
        documentIds: [$customer['documentId']],
    );

    expect(fn () => app(SubmitOrder::class)->handle($data))
        ->toThrow(InsufficientBalanceException::class);

    expect(DB::table('orders')->count())->toBe(0)
        ->and(DB::table('ledger_entries')->where('reference_type', 'order')->count())->toBe(0);

    // Balance remains intact
    $walletBalance = app(WalletReader::class)->getBalance($customer['walletId']);
    expect($walletBalance->minor)->toBe(10_000_00);
});
