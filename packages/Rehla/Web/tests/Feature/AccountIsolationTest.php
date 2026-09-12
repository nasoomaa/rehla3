<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Str;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;

function createTestCustomer(string $email = 'customer@example.com', string $password = 'Secret123!'): GenericUser
{
    $userData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Test Customer',
        email: $email,
        password: $password,
    ));

    return new GenericUser([
        'id' => $userData->id,
        'name' => $userData->name,
        'email' => $userData->email,
        'remember_token' => null,
    ]);
}

it('redirects unauthenticated guests from account routes to login', function (): void {
    test()->get('/account/profile')->assertRedirect('/login');
    test()->get('/account/travelers')->assertRedirect('/login');
    test()->get('/account/wallet')->assertRedirect('/login');
    test()->get('/account/top-ups')->assertRedirect('/login');
    test()->get('/account/orders')->assertRedirect('/login');
    test()->get('/account/notifications')->assertRedirect('/login');
});

it('registers a new customer, logs them in and redirects to account', function (): void {
    $email = 'newcustomer_'.Str::random(6).'@example.com';

    $response = test()->post('/register', [
        'name' => 'New Customer',
        'email' => $email,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect('/account/profile');
    test()->assertAuthenticated('web');
});

it('logs in an existing customer with valid credentials and regenerates session', function (): void {
    $email = 'login_'.Str::random(6).'@example.com';
    createTestCustomer(email: $email, password: 'MyPassword123!');

    $response = test()->post('/login', [
        'email' => $email,
        'password' => 'MyPassword123!',
    ]);

    $response->assertRedirect('/account/profile');
    test()->assertAuthenticated('web');
});

it('rejects invalid login credentials', function (): void {
    $email = 'wrong_'.Str::random(6).'@example.com';
    createTestCustomer(email: $email, password: 'CorrectPassword123!');

    $response = test()->post('/login', [
        'email' => $email,
        'password' => 'WrongPassword!',
    ]);

    $response->assertSessionHasErrors('email');
    test()->assertGuest('web');
});

it('logs out customer and invalidates session', function (): void {
    $customer = createTestCustomer();

    $response = test()->actingAs($customer, 'web')
        ->post('/logout');

    $response->assertRedirect('/');
    test()->assertGuest('web');
});

it('isolates customer account views and never leaks unowned resources', function (): void {
    $customerA = createTestCustomer('customerA_'.Str::random(6).'@example.com');
    $customerB = createTestCustomer('customerB_'.Str::random(6).'@example.com');

    // Customer A has a traveler
    $traveler = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $customerA->id,
        fullName: 'Alice Smith',
        dateOfBirth: '1990-01-01',
        gender: Gender::Female,
        passportNumber: 'P'.Str::random(7),
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));
    $travelerId = $traveler->id;

    // Customer B tries to edit Customer A's traveler -> 404
    test()->actingAs($customerB, 'web')
        ->get("/account/travelers/{$travelerId}/edit")
        ->assertNotFound();

    // Response for Customer B account pages does not mention Alice
    $response = test()->actingAs($customerB, 'web')
        ->get('/account/travelers');
    $response->assertOk()
        ->assertDontSee('Alice');
});
