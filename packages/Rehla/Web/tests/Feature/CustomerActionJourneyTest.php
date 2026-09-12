<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Rehla\Fulfillment\Actions\CreateExecution;
use Rehla\Fulfillment\Actions\RequestCustomerAction;
use Rehla\Fulfillment\Actions\TransitionExecution;
use Rehla\Fulfillment\Data\RequestCustomerActionData;
use Rehla\Fulfillment\Data\TransitionExecutionData;
use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Identity\Enums\ActorType;
use Rehla\Orders\Actions\CreatePaidOrder;
use Rehla\Orders\Data\CreatePaidOrderData;
use Rehla\Orders\Data\FormSnapshotData;
use Rehla\Orders\Data\ServiceSnapshotData;
use Rehla\Orders\Data\TravelerSnapshotData;
use Rehla\Purchasing\Data\CreateExecutionData;
use Rehla\Web\Livewire\Account\CustomerActionResponse;
use Rehla\Web\Livewire\Account\OrderShow;

function createCustomerForOrderJourney(): GenericUser
{
    $userData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Action Customer',
        email: 'action_'.Str::random(6).'@example.com',
        password: 'Password123!',
    ));

    return new GenericUser([
        'id' => $userData->id,
        'name' => $userData->name,
        'email' => $userData->email,
        'remember_token' => null,
    ]);
}

function createPaidOrderWithExecution(string $accountId): array
{
    $serviceId = (string) Str::uuid();
    $travelerId = (string) Str::uuid();
    $formVersionId = (string) Str::uuid();

    $orderData = new CreatePaidOrderData(
        accountId: $accountId,
        serviceId: $serviceId,
        travelerId: $travelerId,
        priceMinor: 50_000_00,
        amountPaidMinor: 50_000_00,
        currency: 'SDG',
        debitLedgerEntryId: (string) Str::uuid(),
        serviceSnapshot: new ServiceSnapshotData(
            nameEn: 'Oman Visa',
            nameAr: 'تأشيرة عمان',
            descriptions: ['short_en' => 'Fast visa'],
            requirements: [],
            expectedDuration: ['en' => '3 days'],
            notes: [],
        ),
        travelerSnapshot: new TravelerSnapshotData(
            fullName: 'Salma Ahmed',
            dateOfBirth: '1998-04-01',
            gender: 'female',
            passportNumber: 'P8877665',
            passportExpiresAt: '2032-01-01',
        ),
        formSnapshot: new FormSnapshotData(
            formVersionId: $formVersionId,
            formVersion: 1,
            formChecksum: hash('sha256', 'schema'),
            schema: ['fields' => []],
            answers: ['visit' => 'Family'],
        ),
    );

    $order = app(CreatePaidOrder::class)->createPaid($orderData);

    $execution = app(CreateExecution::class)->create(new CreateExecutionData(
        orderId: $order->id,
        accountId: $accountId,
        travelerId: $travelerId,
        serviceId: $serviceId,
        formVersionId: $formVersionId,
        documentIds: [],
    ));

    return [$order, $execution];
}

it('shows order snapshot, price, and fulfillment execution status without internal notes', function (): void {
    $customer = createCustomerForOrderJourney();
    [$order, $execution] = createPaidOrderWithExecution($customer->id);

    // Add internal note in fulfillment
    DB::table('execution_internal_notes')->insert([
        'id' => (string) Str::uuid(),
        'execution_id' => $execution->id,
        'staff_id' => (string) Str::uuid(),
        'body' => 'CONFIDENTIAL INTERNAL NOTE - DO NOT LEAK',
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($customer)
        ->test(OrderShow::class, ['orderId' => $order->id]);

    $component->assertOk()
        ->assertSee('Oman Visa')
        ->assertSee('Salma Ahmed')
        ->assertSee('50,000.00')
        ->assertDontSee('CONFIDENTIAL INTERNAL NOTE');
});

it('lets only the owning account answer an open action request', function (): void {
    $customer = createCustomerForOrderJourney();
    $otherCustomer = createCustomerForOrderJourney();
    [$order, $execution] = createPaidOrderWithExecution($customer->id);

    $staffActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        abilities: [AbilityName::ExecutionsManage->value],
    );

    // Transition to under_review first
    app(TransitionExecution::class)->execute(new TransitionExecutionData(
        executionId: $execution->id,
        toStatus: ExecutionStatus::UnderReview,
        actor: $staffActor,
        reason: 'Reviewing application',
    ));

    // Create action request
    $request = app(RequestCustomerAction::class)->execute(new RequestCustomerActionData(
        executionId: $execution->id,
        actor: $staffActor,
        descriptionEn: 'Please provide a clearer passport copy.',
        descriptionAr: 'يرجى تقديم نسخة أوضح من جواز السفر.',
        dueAt: CarbonImmutable::now()->addDays(2),
    ));

    // Other customer cannot answer
    Livewire::actingAs($otherCustomer)
        ->test(CustomerActionResponse::class, ['actionRequestId' => $request->id])
        ->assertForbidden();

    // Owner can view and answer
    Livewire::actingAs($customer)
        ->test(CustomerActionResponse::class, ['actionRequestId' => $request->id])
        ->assertOk()
        ->assertSee('Please provide a clearer passport copy.')
        ->set('message', 'Attached is the high resolution scan.')
        ->call('respond')
        ->assertHasNoErrors()
        ->assertRedirect('/account/orders/'.$order->id);

    // Verify response recorded in DB
    $actionResponse = DB::table('customer_action_responses')
        ->where('action_request_id', $request->id)
        ->first();

    expect($actionResponse)->not->toBeNull()
        ->and($actionResponse->message)->toBe('Attached is the high resolution scan.');
});
