<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Tests\Support\StaffTestHelper;

it('assigns and revokes staff roles with valid MFA', function (): void {
    $admin = StaffTestHelper::createStaff(
        abilities: ['roles.manage'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );
    $targetStaff = StaffTestHelper::createStaff(abilities: ['services.manage']);

    $adminUser = Auth::guard('admin')->getProvider()->retrieveById($admin['id']);

    // 1. Assign role
    $this->actingAs($adminUser, 'admin')->post('/admin/roles-permissions/assign', [
        'user_id' => $targetStaff['id'],
        'role' => 'operations',
    ])->assertRedirect('/admin/roles-permissions');

    // Verify role assignment in target staff actor abilities
    $targetUser = Auth::guard('admin')->getProvider()->retrieveById($targetStaff['id']);
    expect($targetUser->roles->pluck('name'))->toContain('operations');

    // 2. Revoke role
    $this->actingAs($adminUser, 'admin')->post('/admin/roles-permissions/revoke', [
        'user_id' => $targetStaff['id'],
        'role' => 'operations',
    ])->assertRedirect('/admin/roles-permissions');

    $targetUserRefreshed = Auth::guard('admin')->getProvider()->retrieveById($targetStaff['id']);
    expect($targetUserRefreshed->roles->pluck('name'))->not->toContain('operations');
});

it('rejects assigning role to a user without staff profile', function (): void {
    $admin = StaffTestHelper::createStaff(
        abilities: ['roles.manage'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );
    $adminUser = Auth::guard('admin')->getProvider()->retrieveById($admin['id']);

    $customerUserId = (string) \Illuminate\Support\Str::uuid();
    \Illuminate\Support\Facades\DB::table('users')->insert([
        'id' => $customerUserId,
        'name' => 'Plain Customer',
        'email' => 'customer-'.\Illuminate\Support\Str::random(5).'@example.com',
        'password' => bcrypt('secret123'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => $this->actingAs($adminUser, 'admin')->post('/admin/roles-permissions/assign', [
        'user_id' => $customerUserId,
        'role' => 'operations',
    ]))->toThrow(\InvalidArgumentException::class);
});
