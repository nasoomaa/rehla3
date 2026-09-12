<?php

declare(strict_types=1);

namespace Rehla\Api\Tests\Feature;

use Illuminate\Support\Str;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;

it('registers a customer and returns a sanctum bearer token', function (): void {
    $email = 'api_customer_'.Str::random(6).'@example.com';

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'API Customer',
        'email' => $email,
        'password' => 'SecureP@ss123!',
        'password_confirmation' => 'SecureP@ss123!',
        'device_name' => 'test-device',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
            ],
        ]);

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
});

it('logs in an existing customer with valid credentials', function (): void {
    $email = 'api_login_'.Str::random(6).'@example.com';
    $password = 'SecretPass123!';

    app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Login Customer',
        email: $email,
        password: $password,
    ));

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => $password,
        'device_name' => 'mobile-app',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
            ],
        ]);
});

it('rejects login with invalid credentials using problem details', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'WrongPassword123!',
    ]);

    $response->assertStatus(401)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('code', 'INVALID_CREDENTIALS');
});

it('logs out and revokes the current bearer token', function (): void {
    $email = 'api_logout_'.Str::random(6).'@example.com';
    $password = 'SecretPass123!';

    $user = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Logout Customer',
        email: $email,
        password: $password,
    ));

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => $password,
    ]);

    $token = $loginResponse->json('data.token');

    $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout');

    $logoutResponse->assertNoContent();

    auth('sanctum')->forgetUser();

    // Reusing the revoked token should now fail
    $checkResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout');

    $checkResponse->assertUnauthorized();
});
