<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Tests\Support\StaffTestHelper;

it('renders modern categorized layout with theme tokens and navigation sections', function (): void {
    $staff = StaffTestHelper::createStaff(abilities: ['reporting.view']);
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    $response = $this->actingAs($user, 'admin')->get('/admin/overview');

    $response->assertOk();
    $response->assertSee('data-theme', false);
    $response->assertSee('rehla-admin-theme-toggle', false);
    $response->assertSee('ANALYTICS & MONITORING', false);
    $response->assertSee('CATALOG & OPERATIONS', false);
    $response->assertSee('FINANCE & TREASURY', false);
    $response->assertSee('CRM & TRAVELERS', false);
    $response->assertSee('GOVERNANCE & SYSTEM', false);
});

it('provides dialog keyboard accessibility, focus containment, and restoration handlers', function (): void {
    $staff = StaffTestHelper::createStaff(abilities: ['reporting.view']);
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    $response = $this->actingAs($user, 'admin')->get('/admin/overview');

    $response->assertOk();
    $response->assertSee('window.openDrawer', false);
    $response->assertSee('window.closeDrawer', false);
    $response->assertSee('window.openModal', false);
    $response->assertSee('window.closeModal', false);
    $response->assertSee('_triggerElement', false);
    $response->assertSee('setBackgroundInert', false);
    $response->assertSee('focusDialog', false);
    $response->assertSee("e.key === 'Tab'", false);
    $response->assertSee("e.key === 'Escape'", false);
    $response->assertSee('closeAllDialogs', false);
});
