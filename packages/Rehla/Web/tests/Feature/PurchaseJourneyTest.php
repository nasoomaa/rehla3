<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Forms\Actions\CreateFormDraft;
use Rehla\Forms\Actions\PublishFormVersion;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Enums\FieldType;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Web\Livewire\Account\OrderCheckout;

function createPurchasingCustomer(int $balanceMinor = 0): GenericUser
{
    $userData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Buyer Customer',
        email: 'buyer_'.Str::random(6).'@example.com',
        password: 'Password123!',
    ));

    $wallet = app(OpenWallet::class)->execute($userData->id);
    if ($balanceMinor > 0) {
        DB::table('wallets')->where('id', $wallet->id)->update([
            'balance_minor' => $balanceMinor,
        ]);
    }

    return new GenericUser([
        'id' => $userData->id,
        'name' => $userData->name,
        'email' => $userData->email,
        'remember_token' => null,
    ]);
}

function createCheckoutServiceWithForm(int $priceMinor = 50_000_00): array
{
    $serviceData = app(CreateService::class)->execute(new CreateServiceData(
        slug: 'checkout-service-'.Str::random(6),
        nameEn: 'Dubai Visa',
        nameAr: 'تأشيرة دبي',
        shortDescriptionEn: 'Fast visa',
        shortDescriptionAr: 'تأشيرة سريعة',
        detailedDescriptionEn: 'Full 30-day visa',
        detailedDescriptionAr: 'تأشيرة كاملة 30 يوم',
        expectedDurationEn: '2 days',
        expectedDurationAr: 'يومان',
        priceMinor: $priceMinor,
        requirements: [
            ['text_en' => 'Passport', 'text_ar' => 'جواز', 'sort_order' => 1],
        ],
        media: [
            ['document_id' => (string) Str::uuid(), 'alt_en' => 'Pic', 'alt_ar' => 'صورة', 'sort_order' => 1],
        ],
    ), actorId: (string) Str::uuid());

    $publishedService = app(PublishService::class)->execute($serviceData->id, actorId: (string) Str::uuid());

    // Create and publish form
    $fields = [
        new FormFieldData(
            key: 'visit_purpose',
            type: FieldType::ShortText,
            labelEn: 'Purpose of Visit',
            labelAr: 'الغرض من الزيارة',
            required: true,
        ),
    ];
    $draft = app(CreateFormDraft::class)->execute($publishedService->id, $fields, actorId: (string) Str::uuid());

    $publishedForm = app(PublishFormVersion::class)->execute($draft->id, actorId: (string) Str::uuid());

    return [$publishedService, $publishedForm];
}

function createCustomerTraveler(string $customerId, string $name = 'Test Traveler'): string
{
    $traveler = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $customerId,
        fullName: $name,
        dateOfBirth: '1995-05-05',
        gender: Gender::Male,
        passportNumber: 'P'.Str::random(7),
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));

    return $traveler->id;
}

it('validates checkout step progression', function (): void {
    [$service, $form] = createCheckoutServiceWithForm();
    $customer = createPurchasingCustomer(balanceMinor: 100_000_00);
    $travelerId = createCustomerTraveler($customer->id);

    $component = Livewire::actingAs($customer)
        ->test(OrderCheckout::class, ['slug' => $service->slug]);

    expect($component->get('step'))->toBe('traveler');

    // Cannot proceed without selecting traveler
    $component->call('nextStep')
        ->assertHasErrors(['travelerId']);

    $component->set('travelerId', $travelerId)
        ->call('nextStep')
        ->assertHasNoErrors();

    expect($component->get('step'))->toBe('form');
});

it('prevents selecting unowned traveler', function (): void {
    [$service, $form] = createCheckoutServiceWithForm();
    $customer = createPurchasingCustomer(balanceMinor: 100_000_00);
    $otherCustomer = createPurchasingCustomer();
    $unownedTravelerId = createCustomerTraveler($otherCustomer->id, 'Stranger');

    Livewire::actingAs($customer)
        ->test(OrderCheckout::class, ['slug' => $service->slug])
        ->set('travelerId', $unownedTravelerId)
        ->call('nextStep')
        ->assertHasErrors(['travelerId']);
});

it('confirms price freeze during review', function (): void {
    [$service, $form] = createCheckoutServiceWithForm(priceMinor: 50_000_00);
    $customer = createPurchasingCustomer(balanceMinor: 100_000_00);
    $travelerId = createCustomerTraveler($customer->id);

    $component = Livewire::actingAs($customer)
        ->test(OrderCheckout::class, ['slug' => $service->slug])
        ->set('travelerId', $travelerId)
        ->call('nextStep')
        ->set('formData.visit_purpose', 'Tourism')
        ->call('nextStep');

    expect($component->get('step'))->toBe('review')
        ->and($component->get('acceptedPriceMinor'))->toBe(50_000_00)
        ->and($component->get('acceptedPriceVersion'))->toBe(1);
});

it('submits order successfully with wallet debit and idempotency key', function (): void {
    [$service, $form] = createCheckoutServiceWithForm(priceMinor: 50_000_00);
    $customer = createPurchasingCustomer(balanceMinor: 100_000_00);
    $travelerId = createCustomerTraveler($customer->id);

    $test = Livewire::actingAs($customer)
        ->test(OrderCheckout::class, ['slug' => $service->slug])
        ->set('travelerId', $travelerId)
        ->call('nextStep')
        ->set('formData.visit_purpose', 'Tourism and Leisure')
        ->call('nextStep')
        ->call('submitOrder')
        ->assertHasNoErrors()
        ->assertRedirect();

    // Verify order and debit created
    $order = DB::table('orders')->where('account_id', $customer->id)->first();
    expect($order)->not->toBeNull()
        ->and((int) $order->amount_paid_minor)->toBe(50_000_00);

    // Wallet balance debited
    $wallet = DB::table('wallets')->where('account_id', $customer->id)->first();
    expect((int) $wallet->balance_minor)->toBe(50_000_00);
});

it('rejects order when wallet balance is insufficient', function (): void {
    [$service, $form] = createCheckoutServiceWithForm(priceMinor: 50_000_00);
    $customer = createPurchasingCustomer(balanceMinor: 20_000_00); // Less than 50,000 SDG
    $travelerId = createCustomerTraveler($customer->id);

    Livewire::actingAs($customer)
        ->test(OrderCheckout::class, ['slug' => $service->slug])
        ->set('travelerId', $travelerId)
        ->call('nextStep')
        ->set('formData.visit_purpose', 'Tourism')
        ->call('nextStep')
        ->call('submitOrder')
        ->assertHasErrors(['wallet']);
});
