<?php

declare(strict_types=1);

namespace Rehla\Api\Tests\Feature;

use Illuminate\Support\Str;
use Rehla\Identity\Actions\IssueCustomerToken;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;

function createApiUser(): array
{
    $email = 'api_user_'.Str::random(6).'@example.com';
    $user = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Api User',
        email: $email,
        password: 'Password123!',
    ));

    $token = app(IssueCustomerToken::class)->handle($user->id, 'testing');

    return [$user, $token];
}

it('enforces authentication on protected me route', function (): void {
    $this->getJson('/api/v1/me')->assertUnauthorized();

    [$user, $token] = createApiUser();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('allows public access to services catalog', function (): void {
    $response = $this->getJson('/api/v1/services');

    $response->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('enforces owner isolation on travelers endpoint', function (): void {
    [$userA, $tokenA] = createApiUser();
    [$userB, $tokenB] = createApiUser();

    $travelerA = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $userA->id,
        fullName: 'Traveler Owner A',
        dateOfBirth: '1990-01-01',
        gender: Gender::Male,
        passportNumber: 'P'.Str::random(7),
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2030-01-01',
    ));

    // Guest cannot view
    $this->getJson('/api/v1/travelers')->assertUnauthorized();
    $this->getJson('/api/v1/travelers/'.$travelerA->id)->assertUnauthorized();

    // User B cannot view User A's traveler
    $this->withHeader('Authorization', 'Bearer '.$tokenB)
        ->getJson('/api/v1/travelers/'.$travelerA->id)
        ->assertNotFound();

    auth('sanctum')->forgetUser();

    // User A can view their own traveler
    $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->getJson('/api/v1/travelers/'.$travelerA->id)
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Traveler Owner A');
});

it('enforces authentication and owner isolation on wallet endpoint', function (): void {
    $this->getJson('/api/v1/wallet')->assertUnauthorized();

    [$user, $token] = createApiUser();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/wallet');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'currency',
                'balance_minor',
                'balance_formatted',
            ],
        ]);
});

it('allows authenticated users to view active bank accounts', function (): void {
    $this->getJson('/api/v1/bank-accounts')->assertUnauthorized();

    [$user, $token] = createApiUser();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/bank-accounts');

    $response->assertOk()
        ->assertJsonStructure(['data']);
});
