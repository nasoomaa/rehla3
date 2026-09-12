<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Tests\Support\StaffTestHelper;

dataset('admin_sections', [
    ['overview', 'reporting.view'],
    ['services', 'services.manage'],
    ['application-forms', 'forms.manage'],
    ['customers', 'customers.view'],
    ['travelers', 'travelers.view'],
    ['wallets', 'wallets.view'],
    ['bank-accounts', 'bank_accounts.manage'],
    ['top-up-requests', 'topups.review'],
    ['orders', 'orders.view'],
    ['service-executions', 'executions.manage'],
    ['content', 'content.manage'],
    ['notifications', 'notifications.manage'],
    ['roles-permissions', 'roles.manage'],
    ['audit-log', 'audit.view'],
]);

it('redirects unauthenticated users to the admin login page', function (string $section): void {
    $response = $this->get("/admin/{$section}");

    $response->assertRedirect('/admin/login');
})->with('admin_sections');

it('denies customer guard access to admin sections', function (string $section): void {
    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Regular Customer',
        email: 'customer_'.Str::random(6).'@example.test',
        password: 'Password123!',
    ));

    $user = Auth::guard('web')->getProvider()->retrieveById($customer->id);
    $this->actingAs($user, 'web');

    $response = $this->get("/admin/{$section}");

    // Should redirect to admin login because admin guard is not authenticated
    $response->assertRedirect('/admin/login');
})->with('admin_sections');

it('allows staff with the required capability to view the section', function (string $section, string $ability): void {
    $staff = StaffTestHelper::createStaff(
        abilities: [$ability],
        mfaConfirmedAt: CarbonImmutable::now()->subMinutes(10),
    );

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')->get("/admin/{$section}");

    $response->assertOk();
})->with('admin_sections');

it('forbids staff without the required capability from viewing the section', function (string $section, string $ability): void {
    // Staff has some other ability, but not this one
    $otherAbility = $ability === 'reporting.view' ? 'services.manage' : 'reporting.view';
    $staff = StaffTestHelper::createStaff(
        abilities: [$otherAbility],
        mfaConfirmedAt: CarbonImmutable::now()->subMinutes(10),
    );

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')->get("/admin/{$section}");

    $response->assertForbidden();
})->with('admin_sections');

dataset('sensitive_sections', [
    ['top-up-requests', 'topups.review'],
    ['roles-permissions', 'roles.manage'],
    ['audit-log', 'audit.view'],
]);

it('enforces fresh MFA requirement for sensitive admin sections', function (string $section, string $ability): void {
    // 1. Staff with expired MFA (>12 hours) is forbidden
    $expiredStaff = StaffTestHelper::createStaff(
        abilities: [$ability],
        mfaConfirmedAt: CarbonImmutable::now()->subHours(13),
    );

    $expiredUser = Auth::guard('admin')->getProvider()->retrieveById($expiredStaff['id']);
    $this->actingAs($expiredUser, 'admin')
        ->get("/admin/{$section}")
        ->assertForbidden();

    // 2. Staff with fresh MFA (<12 hours) is allowed
    $freshStaff = StaffTestHelper::createStaff(
        abilities: [$ability],
        mfaConfirmedAt: CarbonImmutable::now()->subHours(2),
    );

    $freshUser = Auth::guard('admin')->getProvider()->retrieveById($freshStaff['id']);
    $this->actingAs($freshUser, 'admin')
        ->get("/admin/{$section}")
        ->assertOk();
})->with('sensitive_sections');

it('masks traveler passport numbers in travelers section', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['travelers.view'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );

    // Seed a traveler directly in database
    $travelerId = (string) Str::uuid();
    $ownerId = (string) Str::uuid();
    DB::table('travelers')->insert([
        'id' => $travelerId,
        'owner_id' => $ownerId,
        'full_name' => 'Tariq Ali',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
        'passport_number' => 'P1234567',
        'normalized_passport_number' => 'P1234567',
        'passport_issued_at' => '2020-01-01',
        'passport_expires_at' => '2030-01-01',
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')->get('/admin/travelers');

    $response->assertOk();
    // Raw passport P1234567 must not appear in the view output
    $response->assertDontSee('P1234567');
    // Masked passport should appear
    $response->assertSee('P***');
});

it('omits customer password hash and credentials in customers section', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['customers.view'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );

    $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Secret Customer',
        email: 'secret_'.Str::random(6).'@example.test',
        password: 'SuperSecretPassword999!',
    ));

    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
    $response = $this->actingAs($user, 'admin')->get('/admin/customers');

    $response->assertOk();
    $response->assertSee('Secret Customer');
    $response->assertDontSee('SuperSecretPassword999!');
    $response->assertDontSee('$2y$'); // bcrypt prefix
});
