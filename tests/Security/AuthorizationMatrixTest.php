<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Tests\Support\StaffTestHelper;

beforeEach(function (): void {
    $this->now = CarbonImmutable::now();
});

it('enforces deny-by-default and strict customer guard isolation from admin resources', function (): void {
    // Unauthenticated request visiting admin panel is redirected to admin login
    $this->get('/admin/overview')->assertRedirect('/admin/login');

    // Customer authenticated under web guard visiting admin panel is redirected to admin login
    $customerData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Customer Normal',
        email: 'customer_'.Str::random(8).'@example.com',
        password: 'Password123!',
    ));

    $user = Auth::guard('web')->getProvider()->retrieveById($customerData->id);

    $this->actingAs($user, 'web')
        ->get('/admin/overview')
        ->assertRedirect('/admin/login');

    $this->actingAs($user, 'web')
        ->get('/admin/top-up-requests')
        ->assertRedirect('/admin/login');

    $this->actingAs($user, 'web')
        ->get('/admin/audit-log')
        ->assertRedirect('/admin/login');
});

it('enforces staff capability segregation across administrative sections', function (): void {
    // 1. Staff with only travelers.view ability
    $staff = StaffTestHelper::createStaff(
        abilities: ['travelers.view'],
        name: 'Support Officer'
    );

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    // Allowed to view travelers
    $this->actingAs($user, 'admin')
        ->get('/admin/travelers')
        ->assertOk();

    // Denied from sensitive sections (finance top-ups, audit, roles)
    $this->actingAs($user, 'admin')
        ->get('/admin/top-up-requests')
        ->assertForbidden();

    $this->actingAs($user, 'admin')
        ->get('/admin/audit-log')
        ->assertForbidden();

    $this->actingAs($user, 'admin')
        ->get('/admin/roles-permissions')
        ->assertForbidden();
});

it('prevents IDOR across customer accounts for travelers and orders', function (): void {
    $customerAData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Customer A',
        email: 'customera_'.Str::random(8).'@example.com',
        password: 'Password123!',
    ));

    $customerBData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Customer B',
        email: 'customerb_'.Str::random(8).'@example.com',
        password: 'Password123!',
    ));

    $userA = Auth::guard('web')->getProvider()->retrieveById($customerAData->id);

    // Create traveler belonging to Customer B
    $travelerBId = (string) Str::uuid();
    $rawPassport = 'P'.rand(1000000, 9999999);
    DB::table('travelers')->insert([
        'id' => $travelerBId,
        'owner_id' => $customerBData->id,
        'full_name' => 'Traveler B',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
        'passport_number' => $rawPassport,
        'normalized_passport_number' => $rawPassport,
        'passport_issued_at' => '2020-01-01',
        'passport_expires_at' => '2030-01-01',
        'created_at' => $this->now,
        'updated_at' => $this->now,
    ]);

    // Customer A attempts to view or edit Customer B's traveler in web UI
    $this->actingAs($userA, 'web')
        ->get("/account/travelers/{$travelerBId}/edit")
        ->assertNotFound();

    // Customer A via API attempts to read Customer B's traveler
    $tokenA = $userA->createToken('test')->plainTextToken;
    $this->withToken($tokenA)
        ->getJson("/api/v1/travelers/{$travelerBId}")
        ->assertNotFound();
});

it('revokes Sanctum tokens on logout preventing subsequent API calls', function (): void {
    $customerData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Logout Customer',
        email: 'cust_logout_'.Str::random(8).'@example.com',
        password: 'Password123!',
    ));

    $user = Auth::guard('web')->getProvider()->retrieveById($customerData->id);
    $token = $user->createToken('mobile_app')->plainTextToken;

    // Authenticated request succeeds
    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertOk();

    // Logout revokes token
    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    // Subsequent request with revoked token fails
    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertUnauthorized();
});
