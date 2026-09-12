# Rehla Admin Dashboard Complete Redesign & Full CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transform the Rehla Admin Dashboard into a modern, enterprise-grade operations console with dual-theme support (Dark & Light), a 5-domain categorized sidebar, and complete CRUD workflows across all administrative sections.

**Architecture:** Hybrid UI architecture with Blade templates, Vanilla CSS design tokens (`data-theme`), lightweight Vanilla JS drawers/modals, and domain-action backed controllers adhering strictly to the modular monolith boundaries (Contracts, DTOs, Actions).

**Tech Stack:** Laravel 13.x, PHP 8.5+, PostgreSQL 18, Blade, Vanilla CSS (tokens & glassmorphism), Vanilla JS (accessible drawers & modals), Pest PHP.

**Spec:** [docs/superpowers/specs/2026-09-12-admin-dashboard-redesign-design.md](file:///home/ubuntu/rehla4/docs/superpowers/specs/2026-09-12-admin-dashboard-redesign-design.md)

## Global Constraints
- Currency: SDG (Sudanese Pound) strictly represented as integer minor units (`amount_minor`, scale 100). No floats.
- Modular Monolith Boundaries: Never import `Rehla\<Package>\Models\*` across package boundaries. Use Contracts, DTOs, Actions, or Queries.
- MFA Protection: Sensitive actions (`topups.review`, `roles.manage`, `audit.view`) mandate active MFA within a 12-hour window.
- Code Quality: `declare(strict_types=1);` in every PHP file, explicit typing, strict Pint compliance.

---

### Task 1: Design System CSS, Theme Switcher & Modern Global Shell Layout

**Files:**
- Create: `packages/Rehla/Admin/resources/views/partials/sidebar.blade.php`
- Create: `packages/Rehla/Admin/resources/views/partials/topbar.blade.php`
- Create: `packages/Rehla/Admin/resources/views/partials/toasts.blade.php`
- Modify: `packages/Rehla/Admin/resources/views/layout.blade.php`
- Test: `packages/Rehla/Admin/tests/Feature/AdminLayoutThemeTest.php`

**Interfaces:**
- Consumes: `Auth::guard('admin')->user()`, session alerts (`success`, `error`), errors bag.
- Produces: Global layout shell supporting `data-theme="dark|light"`, categorized 5-domain sidebar, breadcrumb header, MFA badge, sliding drawer/modal triggers.

- [ ] **Step 1: Write the failing test for layout rendering and theme switcher tokens**

```php
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
    $response->assertSee('ANALYTICS & MONITORING');
    $response->assertSee('CATALOG & OPERATIONS');
    $response->assertSee('FINANCE & TREASURY');
    $response->assertSee('CRM & TRAVELERS');
    $response->assertSee('GOVERNANCE & SYSTEM');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminLayoutThemeTest.php`
Expected: FAIL with missing navigation sections or theme attributes.

- [ ] **Step 3: Implement `sidebar.blade.php`, `topbar.blade.php`, `toasts.blade.php`, and modern `layout.blade.php`**

Implement comprehensive CSS variables (`--admin-bg-canvas`, `--admin-bg-surface`, `--admin-border-subtle`, `--admin-primary`, etc.), theme switcher JS, accessible drawer/modal openers, and 5-category sidebar with clean inline SVG icons.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminLayoutThemeTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/Rehla/Admin/resources/views packages/Rehla/Admin/tests
git commit -m "feat(admin): implement modern design system with dark/light mode and enterprise sidebar"
```

---

### Task 2: Service Catalog Complete CRUD & Actions

**Files:**
- Modify: `packages/Rehla/Admin/src/Http/Controllers/ServiceController.php`
- Modify: `packages/Rehla/Admin/routes/admin.php`
- Modify: `packages/Rehla/Admin/resources/views/services/index.blade.php`
- Create: `packages/Rehla/Admin/tests/Feature/AdminServicesCrudTest.php`

**Interfaces:**
- Consumes:
  - `Rehla\Catalog\Actions\CreateService`
  - `Rehla\Catalog\Actions\UpdateServiceContent`
  - `Rehla\Catalog\Actions\ChangeServicePrice`
  - `Rehla\Catalog\Actions\PublishService`
  - `Rehla\Catalog\Actions\DeactivateService`
  - `Rehla\Catalog\Queries\ListAllServices`
- Produces: Full CRUD interface for services (Create, Edit Content, Change Price, Publish, Deactivate).

- [ ] **Step 1: Write the failing test for complete Services CRUD**

```php
<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Tests\Support\StaffTestHelper;

it('performs full CRUD lifecycle on services', function (): void {
    $staff = StaffTestHelper::createStaff(abilities: ['services.manage']);
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    // 1. Create service
    $this->actingAs($user, 'admin')->post('/admin/services', [
        'name_en' => 'Test Service',
        'name_ar' => 'خدمة اختبار',
        'short_description_en' => 'Short EN',
        'short_description_ar' => 'Short AR',
        'detailed_description_en' => 'Detailed EN',
        'detailed_description_ar' => 'Detailed AR',
        'expected_duration_en' => '3 days',
        'expected_duration_ar' => '3 أيام',
        'price_minor' => 1000000,
    ])->assertRedirect('/admin/services');

    $services = app(\Rehla\Catalog\Queries\ListAllServices::class)->execute();
    $service = collect($services)->firstWhere('nameEn', 'Test Service');
    expect($service)->not->toBeNull();

    // 2. Update service content
    $this->actingAs($user, 'admin')->put("/admin/services/{$service->id}/content", [
        'name_en' => 'Updated Service Name',
        'name_ar' => 'اسم محدث',
        'short_description_en' => 'Updated short EN',
        'short_description_ar' => 'Updated short AR',
        'detailed_description_en' => 'Updated detailed EN',
        'detailed_description_ar' => 'Updated detailed AR',
        'expected_duration_en' => '5 days',
        'expected_duration_ar' => '5 أيام',
    ])->assertRedirect('/admin/services');

    // 3. Change price
    $this->actingAs($user, 'admin')->post("/admin/services/{$service->id}/price", [
        'new_price_minor' => 1500000,
    ])->assertRedirect('/admin/services');

    // 4. Publish service
    $this->actingAs($user, 'admin')->post("/admin/services/{$service->id}/publish")
        ->assertRedirect('/admin/services');

    // 5. Deactivate service
    $this->actingAs($user, 'admin')->post("/admin/services/{$service->id}/deactivate")
        ->assertRedirect('/admin/services');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminServicesCrudTest.php`
Expected: FAIL with 404/405 route not defined.

- [ ] **Step 3: Implement controller actions, routes, and sliding drawer UI in `services/index.blade.php`**

Add `updateContent`, `changePrice`, and `deactivate` methods to `ServiceController.php`. Register routes in `routes/admin.php`. Build Create Drawer, Edit Content Drawer, Change Price Modal, and status filter in `resources/views/services/index.blade.php`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminServicesCrudTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/Rehla/Admin/src packages/Rehla/Admin/routes packages/Rehla/Admin/resources packages/Rehla/Admin/tests
git commit -m "feat(admin): complete full CRUD workflows for service catalog"
```

---

### Task 3: Bank Accounts Complete CRUD & Status Toggle

**Files:**
- Modify: `packages/Rehla/Admin/src/Http/Controllers/BankAccountController.php`
- Modify: `packages/Rehla/Admin/routes/admin.php`
- Modify: `packages/Rehla/Admin/resources/views/bank-accounts/index.blade.php`
- Create: `packages/Rehla/Admin/tests/Feature/AdminBankAccountsCrudTest.php`

**Interfaces:**
- Consumes:
  - `Rehla\TopUps\Actions\CreateBankAccount`
  - `Rehla\TopUps\Actions\UpdateBankAccount`
  - `Rehla\TopUps\Actions\DeactivateBankAccount`
  - `Rehla\TopUps\Queries\ListCompanyBankAccounts`
- Produces: Bank accounts CRUD with active status toggle.

- [ ] **Step 1: Write the failing test for Bank Accounts CRUD**

```php
<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Tests\Support\StaffTestHelper;

it('manages bank accounts with full CRUD and active status toggle', function (): void {
    $staff = StaffTestHelper::createStaff(abilities: ['bank_accounts.manage']);
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    // 1. Create bank account
    $this->actingAs($user, 'admin')->post('/admin/bank-accounts', [
        'bank_name_en' => 'Omdurman National Bank',
        'bank_name_ar' => 'بنك أمدرمان الوطني',
        'account_number' => '1234567890',
        'beneficiary_name' => 'Rehla Travel Co.',
        'sort_order' => 1,
    ])->assertRedirect('/admin/bank-accounts');

    $accounts = app(\Rehla\TopUps\Queries\ListCompanyBankAccounts::class)->all();
    $account = collect($accounts)->firstWhere('accountNumber', '1234567890');
    expect($account)->not->toBeNull();

    // 2. Update bank account
    $this->actingAs($user, 'admin')->put("/admin/bank-accounts/{$account->id}", [
        'bank_name_en' => 'ONB Updated',
        'bank_name_ar' => 'بنك أمدرمان المحدث',
        'account_number' => '1234567890',
        'beneficiary_name' => 'Rehla Travel Services',
        'sort_order' => 2,
    ])->assertRedirect('/admin/bank-accounts');

    // 3. Deactivate / toggle
    $this->actingAs($user, 'admin')->post("/admin/bank-accounts/{$account->id}/deactivate")
        ->assertRedirect('/admin/bank-accounts');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminBankAccountsCrudTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement BankAccountController methods, routes, and interactive UI**

Implement `update` and `deactivate` in `BankAccountController.php`. Add routes in `routes/admin.php`. Upgrade `resources/views/bank-accounts/index.blade.php` with Create Modal, Edit Modal, and Toggle Active action.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminBankAccountsCrudTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/Rehla/Admin/src packages/Rehla/Admin/routes packages/Rehla/Admin/resources packages/Rehla/Admin/tests
git commit -m "feat(admin): complete bank accounts CRUD and active state toggle"
```

---

### Task 4: Dynamic Application Forms Visual Builder & Draft Management

**Files:**
- Modify: `packages/Rehla/Admin/src/Http/Controllers/FormController.php`
- Modify: `packages/Rehla/Admin/routes/admin.php`
- Modify: `packages/Rehla/Admin/resources/views/forms/index.blade.php`
- Create: `packages/Rehla/Admin/tests/Feature/AdminFormsManagementTest.php`

**Interfaces:**
- Consumes:
  - `Rehla\Forms\Actions\CreateFormDraft`
  - `Rehla\Forms\Actions\UpdateFormDraft`
  - `Rehla\Forms\Actions\PublishFormVersion`
  - `Rehla\Forms\Queries\GetFormVersion`
- Produces: Dynamic field editor, preview modal, and version publishing.

- [ ] **Step 1: Write failing test for Form draft creation, schema updating, and publishing**

```php
<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Tests\Support\StaffTestHelper;

it('creates, updates draft schema, and publishes application form versions', function (): void {
    $staff = StaffTestHelper::createStaff(abilities: ['forms.manage']);
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    // Create service first to attach form
    $serviceId = (string) \Illuminate\Support\Str::uuid();
    \Illuminate\Support\Facades\DB::table('services')->insert([
        'id' => $serviceId,
        'slug' => 'visa-service-'.\Illuminate\Support\Str::random(5),
        'status' => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 1. Create draft
    $response = $this->actingAs($user, 'admin')->post("/admin/application-forms/{$serviceId}/draft", [
        'schema_json' => json_encode([
            'fields' => [
                ['name' => 'passport_no', 'type' => 'text', 'label_en' => 'Passport No', 'label_ar' => 'رقم الجواز', 'required' => true],
            ],
        ]),
    ]);
    $response->assertRedirect('/admin/application-forms');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminFormsManagementTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement FormController draft handlers, routes, and visual field builder view**

Implement `createDraft`, `updateDraft`, and `publishVersion` in `FormController.php`. Add schema builder UI with live JSON parsing and preview modal in `forms/index.blade.php`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminFormsManagementTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/Rehla/Admin/src packages/Rehla/Admin/routes packages/Rehla/Admin/resources packages/Rehla/Admin/tests
git commit -m "feat(admin): implement dynamic application forms draft editor and publisher"
```

---

### Task 5: Roles & Permissions Matrix and Staff Role Assignment

**Files:**
- Modify: `packages/Rehla/Admin/src/Http/Controllers/RolePermissionController.php`
- Modify: `packages/Rehla/Admin/routes/admin.php`
- Modify: `packages/Rehla/Admin/resources/views/roles/index.blade.php`
- Create: `packages/Rehla/Admin/tests/Feature/AdminRolesAssignmentTest.php`

**Interfaces:**
- Consumes:
  - `Rehla\Identity\Actions\AssignRole`
  - `Rehla\Identity\Actions\RevokeRole`
  - `Rehla\Identity\Queries\ListAllRoles`
- Produces: Roles capabilities matrix and staff assignment drawer (MFA protected).

- [ ] **Step 1: Write failing test for Role assignment and revocation**

```php
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

    // Assign role
    $this->actingAs($adminUser, 'admin')->post('/admin/roles-permissions/assign', [
        'user_id' => $targetStaff['id'],
        'role' => 'operations',
    ])->assertRedirect('/admin/roles-permissions');

    // Revoke role
    $this->actingAs($adminUser, 'admin')->post('/admin/roles-permissions/revoke', [
        'user_id' => $targetStaff['id'],
        'role' => 'operations',
    ])->assertRedirect('/admin/roles-permissions');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminRolesAssignmentTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement assignment handlers and visual matrix in `roles/index.blade.php`**

Add `assign` and `revoke` in `RolePermissionController.php`. Add routes protected by `admin.ability:roles.manage`. Build modern capability grid and staff assignment drawer.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminRolesAssignmentTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/Rehla/Admin/src packages/Rehla/Admin/routes packages/Rehla/Admin/resources packages/Rehla/Admin/tests
git commit -m "feat(admin): implement roles and permissions matrix with staff role assignment"
```

---

### Task 6: Modernize Orders, Service Executions & Top-Up Approvals

**Files:**
- Modify: `packages/Rehla/Admin/resources/views/top-ups/index.blade.php`
- Modify: `packages/Rehla/Admin/resources/views/orders/index.blade.php`
- Modify: `packages/Rehla/Admin/resources/views/executions/index.blade.php`
- Modify: `packages/Rehla/Admin/resources/views/executions/show.blade.php`
- Modify: `packages/Rehla/Admin/tests/Feature/AdminActionsTest.php`

**Interfaces:**
- Consumes:
  - `Rehla\TopUps\Actions\ApproveTopUp`, `RejectTopUp`
  - `Rehla\Fulfillment\Actions\TransitionExecution`, `AddExecutionNote`, `RequestCustomerAction`
- Produces: High-aesthetic review cards, receipt modal, execution lifecycle timeline, transition modal.

- [ ] **Step 1: Write test verifying execution actions and topup modals**

```php
<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Tests\Support\StaffTestHelper;

it('renders modernized execution lifecycle and topup approval cards', function (): void {
    $staff = StaffTestHelper::createStaff(
        abilities: ['executions.manage', 'topups.review', 'orders.view'],
        mfaConfirmedAt: CarbonImmutable::now(),
    );
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    $this->actingAs($user, 'admin')->get('/admin/top-up-requests')->assertOk();
    $this->actingAs($user, 'admin')->get('/admin/orders')->assertOk();
    $this->actingAs($user, 'admin')->get('/admin/service-executions')->assertOk();
});
```

- [ ] **Step 2: Run test to verify it passes baseline**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminActionsTest.php`

- [ ] **Step 3: Upgrade views with modern cards, receipt dialogs, and lifecycle timelines**

Update `top-ups/index.blade.php`, `orders/index.blade.php`, and `executions/show.blade.php` to use the unified design tokens, status badges, action dialogs, and timeline indicators.

- [ ] **Step 4: Run tests to verify all feature tests pass**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminActionsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/Rehla/Admin/resources packages/Rehla/Admin/tests
git commit -m "feat(admin): modernize orders, executions lifecycle, and top-up review views"
```

---

### Task 7: Modernize CRM, Content CMS, Notifications & Overview Dashboard

**Files:**
- Modify: `packages/Rehla/Admin/resources/views/overview/index.blade.php`
- Modify: `packages/Rehla/Admin/resources/views/customers/index.blade.php`
- Modify: `packages/Rehla/Admin/resources/views/travelers/index.blade.php`
- Modify: `packages/Rehla/Admin/resources/views/wallets/index.blade.php`
- Modify: `packages/Rehla/Admin/resources/views/content/index.blade.php`
- Modify: `packages/Rehla/Admin/resources/views/notifications/index.blade.php`
- Modify: `packages/Rehla/Admin/resources/views/audit/index.blade.php`

**Interfaces:**
- Consumes: Metrics DTOs, Customers queries, Travelers masked records, Ledger views, Content blocks, Audit timeline.
- Produces: Completely redesigned, cohesive UI across all remaining admin views.

- [ ] **Step 1: Write test asserting clean rendering of all remaining admin views**

```php
<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Tests\Support\StaffTestHelper;

it('renders all CRM, content, and governance sections with modern design tokens', function (string $url, string $ability): void {
    $staff = StaffTestHelper::createStaff(
        abilities: [$ability],
        mfaConfirmedAt: CarbonImmutable::now(),
    );
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    $response = $this->actingAs($user, 'admin')->get($url);
    $response->assertOk();
    $response->assertSee('class="admin-card"', false);
})->with([
    ['/admin/overview', 'reporting.view'],
    ['/admin/customers', 'customers.view'],
    ['/admin/travelers', 'travelers.view'],
    ['/admin/wallets', 'wallets.view'],
    ['/admin/content', 'content.manage'],
    ['/admin/notifications', 'notifications.manage'],
    ['/admin/audit-log', 'audit.view'],
]);
```

- [ ] **Step 2: Upgrade views with modern cards, tables, search bars, and statistics grids**

Apply cohesive styling, card elevation, filter inputs, and status badges across `overview`, `customers`, `travelers`, `wallets`, `content`, `notifications`, and `audit`.

- [ ] **Step 3: Run test to verify all views render successfully**

Run: `php artisan test packages/Rehla/Admin/tests/Feature/AdminStaffJourneyTest.php`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add packages/Rehla/Admin/resources packages/Rehla/Admin/tests
git commit -m "feat(admin): modernize overview dashboard, CRM, content CMS, and audit views"
```

---

### Task 8: Full Architecture Compliance & System Verification

**Files:**
- Test: `tests/Architecture/ModelBoundaryTest.php`
- Test: `tests/Architecture/PackageDependencyTest.php`
- Test: `packages/Rehla/Admin/tests`

- [ ] **Step 1: Run full architecture test suite**

Run: `php artisan test tests/Architecture`
Expected: 100% PASS with 0 boundary leaks.

- [ ] **Step 2: Run full Admin package test suite**

Run: `php artisan test packages/Rehla/Admin`
Expected: All tests pass.

- [ ] **Step 3: Run code style formatter and check**

Run: `./vendor/bin/pint --test`
Expected: Clean formatting.

- [ ] **Step 4: Run full verification gate**

Run: `composer verify`
Expected: All parallel Pest tests and Pint pass.

- [ ] **Step 5: Final Commit**

```bash
git commit -m "release: finalize Rehla modern admin dashboard redesign and full CRUD capabilities"
```
