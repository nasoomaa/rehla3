<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Rehla\Identity\Actions\AssignRole;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Contracts\AuthorizesActor;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Identity\Models\Ability;
use Rehla\Identity\Models\Role;
use Rehla\Identity\Models\StaffProfile;
use Rehla\Identity\Models\User;

it('staff without the ability gets 403', function (): void {
    $user = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Staff Member',
        email: 'staff@example.test',
        password: 'Secret-12345',
    ));

    // No role assigned — every staff ability denied
    expect(app(AuthorizesActor::class)->allows($user->actor, AbilityName::ServicesManage))->toBeFalse();
});

it('sensitive abilities require recent MFA confirmation within 12h window', function (): void {
    $user = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Admin User',
        email: 'admin@example.test',
        password: 'Secret-12345',
    ));

    $sensitiveAbilities = [
        AbilityName::TopUpsReview,
        AbilityName::RolesManage,
        AbilityName::AuditView,
    ];

    foreach ($sensitiveAbilities as $ability) {
        expect(app(AuthorizesActor::class)->allows($user->actor, $ability))->toBeFalse();
    }
});

it('staff with assigned ability and valid MFA is granted access', function (): void {
    $userData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Authorized Reviewer',
        email: 'reviewer@example.test',
        password: 'Secret-12345',
    ));

    $user = User::findOrFail($userData->id);

    // Create staff profile with fresh MFA (1 hour ago)
    StaffProfile::create([
        'id' => (string) Str::uuid(),
        'user_id' => $user->id,
        'mfa_confirmed_at' => CarbonImmutable::now()->subHour(),
    ]);

    // Create role and attach TopUpsReview ability
    $role = Role::create([
        'id' => (string) Str::uuid(),
        'name' => 'financial_reviewer',
        'label' => 'Financial Reviewer',
    ]);
    $ability = Ability::create([
        'id' => (string) Str::uuid(),
        'name' => AbilityName::TopUpsReview->value,
    ]);
    $role->abilities()->attach($ability->id);

    app(AssignRole::class)->handle($user->id, 'financial_reviewer');

    $actor = $user->fresh()->toActorData();
    expect(app(AuthorizesActor::class)->allows($actor, AbilityName::TopUpsReview))->toBeTrue();
});

it('denies sensitive ability when MFA is older than 12 hours', function (): void {
    $userData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Expired Reviewer',
        email: 'expired_mfa@example.test',
        password: 'Secret-12345',
    ));

    $user = User::findOrFail($userData->id);

    // Create staff profile with expired MFA (13 hours ago)
    StaffProfile::create([
        'id' => (string) Str::uuid(),
        'user_id' => $user->id,
        'mfa_confirmed_at' => CarbonImmutable::now()->subHours(13),
    ]);

    $role = Role::create([
        'id' => (string) Str::uuid(),
        'name' => 'auditor',
        'label' => 'Auditor',
    ]);
    $ability = Ability::create([
        'id' => (string) Str::uuid(),
        'name' => AbilityName::AuditView->value,
    ]);
    $role->abilities()->attach($ability->id);

    app(AssignRole::class)->handle($user->id, 'auditor');

    $actor = $user->fresh()->toActorData();
    expect(app(AuthorizesActor::class)->allows($actor, AbilityName::AuditView))->toBeFalse();
});

it('enforces customer and admin guard isolation', function (): void {
    // 1. Create pure customer
    $customerData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Regular Customer',
        email: 'customer@example.test',
        password: 'Secret-12345',
    ));
    $customer = User::findOrFail($customerData->id);

    // Authenticate on web guard
    Auth::guard('web')->login($customer);
    expect(Auth::guard('web')->check())->toBeTrue();

    // Verify customer is NOT authenticated on admin guard
    expect(Auth::guard('admin')->check())->toBeFalse()
        ->and(Auth::guard('admin')->user())->toBeNull();

    // Verify admin provider rejects authenticating a customer lacking a staff profile
    $credentials = ['email' => 'customer@example.test', 'password' => 'Secret-12345'];
    $attemptResult = Auth::guard('admin')->attempt($credentials);
    expect($attemptResult)->toBeFalse();

    // 2. Create staff user
    $staffData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Ops Staff',
        email: 'ops@example.test',
        password: 'Secret-12345',
    ));
    $staff = User::findOrFail($staffData->id);
    StaffProfile::create([
        'id' => (string) Str::uuid(),
        'user_id' => $staff->id,
        'mfa_confirmed_at' => CarbonImmutable::now(),
    ]);

    // Staff CAN authenticate on admin guard
    $staffAttempt = Auth::guard('admin')->attempt(['email' => 'ops@example.test', 'password' => 'Secret-12345']);
    expect($staffAttempt)->toBeTrue()
        ->and(Auth::guard('admin')->id())->toBe($staff->id);
});
