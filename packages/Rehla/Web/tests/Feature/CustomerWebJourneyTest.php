<?php

declare(strict_types=1);

namespace Rehla\Web\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Forms\Actions\CreateFormDraft;
use Rehla\Forms\Actions\PublishFormVersion;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Enums\FieldType;
use Rehla\Fulfillment\Actions\TransitionExecution;
use Rehla\Fulfillment\Data\TransitionExecutionData;
use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\Purchasing\Actions\SubmitOrder;
use Rehla\Purchasing\Data\SubmitOrderData;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Web\Livewire\Account\OrderCheckout;
use Rehla\Web\Livewire\Account\TopUpCreate;

function setupCustomerJourneyService(): array
{
    $actorId = (string) Str::uuid();
    $serviceData = app(CreateService::class)->execute(new CreateServiceData(
        slug: 'journey-visa-'.Str::random(6),
        nameEn: 'UAE Express Visa',
        nameAr: 'تأشيرة الإمارات السريعة',
        shortDescriptionEn: 'Fast tourist visa for Dubai',
        shortDescriptionAr: 'تأشيرة سياحية سريعة إلى دبي',
        detailedDescriptionEn: 'Comprehensive visa processing',
        detailedDescriptionAr: 'معالجة شاملة للتأشيرة',
        expectedDurationEn: '2 days',
        expectedDurationAr: 'يومان',
        priceMinor: 50_000_00,
        requirements: [
            ['text_en' => 'Passport copy', 'text_ar' => 'صورة الجواز', 'sort_order' => 1],
        ],
        media: [
            ['document_id' => (string) Str::uuid(), 'alt_en' => 'Banner', 'alt_ar' => 'شعار', 'sort_order' => 1],
        ],
    ), actorId: $actorId);

    $publishedService = app(PublishService::class)->execute($serviceData->id, actorId: $actorId);

    $fields = [
        new FormFieldData(
            key: 'visit_reason',
            type: FieldType::ShortText,
            labelEn: 'Reason for visit',
            labelAr: 'سبب الزيارة',
            required: true,
        ),
    ];

    $draft = app(CreateFormDraft::class)->execute($publishedService->id, $fields, actorId: $actorId);
    $publishedForm = app(PublishFormVersion::class)->execute($draft->id, actorId: $actorId);

    return [$publishedService, $publishedForm];
}

it('verifies customer journey step browse_catalog_public', function (): void {
    [$service] = setupCustomerJourneyService();

    // 1. Visit homepage
    $response = $this->get('/');
    $response->assertOk();
    $response->assertSee('Rehla');

    // 2. Visit services catalog
    $catalogResponse = $this->get('/services');
    $catalogResponse->assertOk();
    $catalogResponse->assertSee($service->nameEn);
});

it('verifies customer journey step whatsapp_inquiry_button', function (): void {
    [$service] = setupCustomerJourneyService();

    $response = $this->get('/services/'.$service->slug);
    $response->assertOk();
    $response->assertSee('wa.me');
    $response->assertSee($service->nameEn);
});

it('verifies customer journey step account_registration', function (): void {
    $email = 'newcustomer_'.Str::random(6).'@example.test';

    $response = $this->post('/register', [
        'name' => 'Sara Mohammed',
        'email' => $email,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect('/account/profile');
    expect(Auth::guard('web')->check())->toBeTrue();
    expect(Auth::guard('web')->user()->email)->toBe(strtolower($email));
});

it('verifies customer journey step login_session', function (): void {
    $email = strtolower('login_'.Str::random(6).'@example.test');
    app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Login Customer',
        email: $email,
        password: 'Password123!',
    ));

    $response = $this->post('/login', [
        'email' => $email,
        'password' => 'Password123!',
    ]);

    $response->assertRedirect('/account/profile');
    expect(Auth::guard('web')->check())->toBeTrue();
    expect(Auth::guard('web')->user()->email)->toBe($email);
});

it('verifies customer journey step check_wallet_zero_balance', function (): void {
    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Wallet Customer',
        email: 'wallet_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));
    app(OpenWallet::class)->execute($customer->id);

    $user = Auth::guard('web')->getProvider()->retrieveById($customer->id);
    $this->actingAs($user, 'web');

    $response = $this->get('/account/wallet');
    $response->assertOk();
    $response->assertSee('0.00');
    $response->assertSee('SDG');
});

it('verifies customer journey step view_bank_accounts', function (): void {
    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Bank View Customer',
        email: 'bankview_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));
    app(OpenWallet::class)->execute($customer->id);

    // Seed a bank account
    $bankAccountId = (string) Str::uuid();
    DB::table('company_bank_accounts')->insert([
        'id' => $bankAccountId,
        'bank_name_en' => 'Faisal Islamic Bank',
        'bank_name_ar' => 'بنك فيصل الإسلامي',
        'account_number' => '9876543210',
        'beneficiary_name' => 'Rehla Travel Services',
        'active' => true,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = Auth::guard('web')->getProvider()->retrieveById($customer->id);

    Livewire::actingAs($user)
        ->test(TopUpCreate::class)
        ->assertSee('Faisal Islamic Bank')
        ->assertSee('9876543210');
});

it('verifies customer journey step submit_topup_receipt', function (): void {
    Storage::fake('private');

    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Receipt Customer',
        email: 'receipt_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));
    $wallet = app(OpenWallet::class)->execute($customer->id);

    $bankAccountId = (string) Str::uuid();
    DB::table('company_bank_accounts')->insert([
        'id' => $bankAccountId,
        'bank_name_en' => 'Bank of Khartoum',
        'bank_name_ar' => 'بنك الخرطوم',
        'account_number' => '1122334455',
        'beneficiary_name' => 'Rehla Co',
        'active' => true,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = Auth::guard('web')->getProvider()->retrieveById($customer->id);

    $ref = 'REF-'.Str::random(8);
    Livewire::actingAs($user)
        ->test(TopUpCreate::class)
        ->set('bankAccountId', $bankAccountId)
        ->set('amount', '5000.00')
        ->set('reference', $ref)
        ->set('receipt', UploadedFile::fake()->image('receipt.jpg'))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect('/account/top-ups');

    $topUp = DB::table('topup_requests')->where('transaction_reference', $ref)->first();
    expect($topUp)->not->toBeNull();
    expect((int) $topUp->amount_minor)->toBe(500_000);
});

it('verifies customer journey step save_traveler_profile', function (): void {
    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Traveler Owner',
        email: 'traveler_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));
    $user = Auth::guard('web')->getProvider()->retrieveById($customer->id);
    $this->actingAs($user, 'web');

    $passport = 'P'.rand(1000000, 9999999);
    $response = $this->post('/account/travelers', [
        'full_name' => 'Ali Babiker',
        'date_of_birth' => '1992-03-15',
        'gender' => 'male',
        'passport_number' => $passport,
        'passport_issued_at' => '2020-01-01',
        'passport_expires_at' => '2030-01-01',
    ]);

    $response->assertRedirect('/account/travelers');

    $traveler = DB::table('travelers')->where('owner_id', $customer->id)->first();
    expect($traveler)->not->toBeNull();
    expect($traveler->full_name)->toBe('Ali Babiker');
});

it('verifies customer journey step select_service_and_form', function (): void {
    [$service, $form] = setupCustomerJourneyService();

    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Select Service Customer',
        email: 'selectsvc_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));
    $traveler = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $customer->id,
        fullName: 'Traveler One',
        dateOfBirth: '1990-01-01',
        gender: Gender::Male,
        passportNumber: 'P'.rand(1000000, 9999999),
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));
    $user = Auth::guard('web')->getProvider()->retrieveById($customer->id);

    $component = Livewire::actingAs($user)
        ->test(OrderCheckout::class, ['slug' => $service->slug]);

    $component->assertSee($service->nameEn);
    $component->set('travelerId', $traveler->id)
        ->call('nextStep')
        ->assertSee('Reason for visit');
});

it('verifies customer journey step fill_service_form', function (): void {
    [$service, $form] = setupCustomerJourneyService();

    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Fill Form Customer',
        email: 'fillform_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));
    $wallet = app(OpenWallet::class)->execute($customer->id);
    DB::table('wallets')->where('id', $wallet->id)->update(['balance_minor' => 100_000_00]);

    $traveler = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $customer->id,
        fullName: 'Traveler One',
        dateOfBirth: '1990-01-01',
        gender: Gender::Male,
        passportNumber: 'P'.rand(1000000, 9999999),
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));

    $user = Auth::guard('web')->getProvider()->retrieveById($customer->id);

    $component = Livewire::actingAs($user)
        ->test(OrderCheckout::class, ['slug' => $service->slug])
        ->set('travelerId', $traveler->id)
        ->call('nextStep')
        ->assertSet('step', 'form')
        ->set('formData.visit_reason', 'Tourism and Leisure')
        ->call('nextStep')
        ->assertSet('step', 'review')
        ->assertSee('Review Order & Confirm Payment');
});

it('verifies customer journey step checkout_order_with_wallet', function (): void {
    [$service, $form] = setupCustomerJourneyService();

    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Checkout Customer',
        email: 'checkout_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));
    $wallet = app(OpenWallet::class)->execute($customer->id);
    DB::table('wallets')->where('id', $wallet->id)->update(['balance_minor' => 100_000_00]);

    $traveler = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $customer->id,
        fullName: 'Traveler Two',
        dateOfBirth: '1991-02-02',
        gender: Gender::Female,
        passportNumber: 'P'.rand(1000000, 9999999),
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));

    $user = Auth::guard('web')->getProvider()->retrieveById($customer->id);

    $component = Livewire::actingAs($user)
        ->test(OrderCheckout::class, ['slug' => $service->slug])
        ->set('travelerId', $traveler->id)
        ->call('nextStep')
        ->set('formData.visit_reason', 'Family visit')
        ->call('nextStep')
        ->call('submitOrder')
        ->assertHasNoErrors();

    // Check order created
    $order = DB::table('orders')->where('account_id', $customer->id)->first();
    expect($order)->not->toBeNull();
    expect((int) $order->amount_paid_minor)->toBe(50_000_00);

    // Check wallet balance debited
    $walletAfter = DB::table('wallets')->where('id', $wallet->id)->first();
    expect((int) $walletAfter->balance_minor)->toBe(50_000_00);
});

it('verifies customer journey step track_fulfillment_status', function (): void {
    [$service, $form] = setupCustomerJourneyService();

    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Track Customer',
        email: 'track_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));
    $wallet = app(OpenWallet::class)->execute($customer->id);
    DB::table('wallets')->where('id', $wallet->id)->update(['balance_minor' => 100_000_00]);

    $traveler = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $customer->id,
        fullName: 'Traveler Three',
        dateOfBirth: '1985-05-05',
        gender: Gender::Male,
        passportNumber: 'P'.rand(1000000, 9999999),
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));

    $result = app(SubmitOrder::class)->execute(new SubmitOrderData(
        accountId: $customer->id,
        serviceId: $service->id,
        travelerId: $traveler->id,
        acceptedPriceMinor: $service->currentPriceMinor,
        acceptedPriceVersion: $service->priceVersion,
        formVersionId: $form->id,
        idempotencyKey: 'TRK-'.Str::random(8),
        answers: ['visit_reason' => 'Business'],
    ));

    $order = DB::table('orders')->where('id', $result->orderId)->first();
    expect($order)->not->toBeNull();

    $user = Auth::guard('web')->getProvider()->retrieveById($customer->id);
    $this->actingAs($user, 'web');

    $response = $this->get('/account/orders/'.$order->id);
    $response->assertOk();
    $response->assertSee('received');
});

it('verifies customer journey step download_completion_document', function (): void {
    [$service, $form] = setupCustomerJourneyService();

    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Doc Customer',
        email: 'doc_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));
    $wallet = app(OpenWallet::class)->execute($customer->id);
    DB::table('wallets')->where('id', $wallet->id)->update(['balance_minor' => 100_000_00]);

    $traveler = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $customer->id,
        fullName: 'Traveler Four',
        dateOfBirth: '1988-08-08',
        gender: Gender::Male,
        passportNumber: 'P'.rand(1000000, 9999999),
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));

    $result = app(SubmitOrder::class)->execute(new SubmitOrderData(
        accountId: $customer->id,
        serviceId: $service->id,
        travelerId: $traveler->id,
        acceptedPriceMinor: $service->currentPriceMinor,
        acceptedPriceVersion: $service->priceVersion,
        formVersionId: $form->id,
        idempotencyKey: 'DOC-'.Str::random(8),
        answers: ['visit_reason' => 'Tourism'],
    ));

    $execution = DB::table('service_executions')->where('order_id', $result->orderId)->first();
    expect($execution)->not->toBeNull();

    $staffActor = new ActorData(
        id: (string) Str::uuid(),
        type: 'staff',
        abilities: ['executions.manage'],
    );

    // 1. Under review
    app(TransitionExecution::class)->execute(new TransitionExecutionData(
        executionId: $execution->id,
        toStatus: ExecutionStatus::UnderReview,
        actor: $staffActor,
    ));

    // 2. In processing
    app(TransitionExecution::class)->execute(new TransitionExecutionData(
        executionId: $execution->id,
        toStatus: ExecutionStatus::InProcessing,
        actor: $staffActor,
    ));

    // 3. Complete
    app(TransitionExecution::class)->execute(new TransitionExecutionData(
        executionId: $execution->id,
        toStatus: ExecutionStatus::Completed,
        actor: $staffActor,
    ));

    $user = Auth::guard('web')->getProvider()->retrieveById($customer->id);
    $this->actingAs($user, 'web');

    $response = $this->get('/account/orders/'.$result->orderId);
    $response->assertOk();
    $response->assertSee('completed');
});
