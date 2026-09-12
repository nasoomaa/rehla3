# Rehla Admin Dashboard Complete Redesign & Full CRUD Specification

- **Date**: 2026-09-12
- **Author**: Antigravity & Engineering Team
- **Package**: `packages/Rehla/Admin`
- **Status**: Approved for Implementation

---

## 1. Executive Summary & Goals

The Rehla (رحلة) administrative dashboard currently provides basic operational capabilities but suffers from:
1. An overly simplistic, single-theme dark UI without light mode support.
2. An unorganized, flat 14-item sidebar with emojis, unlike the structured, categorized navigation of world-class commerce and operations dashboards (Shopify, Salla, Stripe).
3. Incomplete CRUD workflows (e.g., Services only has Create and Publish without Edit Content, Change Price, or Deactivate; Bank Accounts lacks Edit/Deactivate; Forms lacks interactive draft updating; Roles lacks assignment controls).

### Key Objectives
- **Enterprise-Grade UI/UX**: Deliver a modern, high-aesthetic interface with dual-theme support (Dark & Light mode), responsive typography, clean glassmorphism cards, micro-interactions, and accessible contrast.
- **Categorized Navigation Architecture**: Reorganize the sidebar into 5 logical enterprise domains with SVG icons, collapsible menus, and actionable alert badges.
- **Complete CRUD & Domain Action Coverage**: Upgrade all admin sections to support full lifecycle management (Create, Read/Filter, Update, Status Actions, Deactivation) backed strictly by domain Actions, Contracts, and DTOs without breaking modular monolith boundaries.

---

## 2. Design System & Theming Architecture

### 2.1 Color Tokens & Theming (`data-theme`)
The application supports seamless toggling between Dark (default for operations) and Light modes via CSS custom properties on `html[data-theme="dark"]` and `html[data-theme="light"]`:

| Token | Dark Mode (`dark`) | Light Mode (`light`) | Description |
|---|---|---|---|
| `--admin-bg-canvas` | `#080d1c` | `#f1f5f9` | Global viewport background |
| `--admin-bg-surface` | `#0f1a36` | `#ffffff` | Primary card and drawer surface |
| `--admin-bg-surface-elevated` | `#16254c` | `#f8fafc` | Hovered rows, inputs, sub-panels |
| `--admin-border-subtle` | `rgba(255, 255, 255, 0.08)` | `rgba(0, 0, 0, 0.08)` | Dividers, card borders, table lines |
| `--admin-border-focus` | `#3b82f6` | `#2563eb` | Active inputs, primary focus outlines |
| `--admin-text-primary` | `#f8fafc` | `#0f172a` | High-contrast main headings and body text |
| `--admin-text-muted` | `#94a3b8` | `#64748b` | Secondary captions, timestamps, table th |
| `--admin-primary` | `#3b82f6` | `#2563eb` | Brand interactive accent |
| `--admin-primary-hover` | `#2563eb` | `#1d4ed8` | Accent hover state |
| `--admin-success` | `#10b981` | `#059669` | Success badges, approval buttons |
| `--admin-danger` | `#ef4444` | `#dc2626` | Destructive/deactivate actions, errors |
| `--admin-warning` | `#f59e0b` | `#d97706` | Pending badges, review alerts |

### 2.2 Vanilla UI Interactivity (`admin.js`)
Zero heavyweight external JavaScript frameworks. A clean, modular Vanilla JS engine provides:
- **Theme Persistence**: Automatic detection of `prefers-color-scheme` with persistence to `localStorage.getItem('rehla_admin_theme')`.
- **Sliding Drawers & Modals**: Smooth animated slide-over drawers (Right-to-Left or Left-to-Right depending on `dir="rtl|ltr"`) with backdrop blur, accessible focus trapping, and `Escape` key listeners.
- **Client-Side Live Filtering & Search**: Instant row filtering on table pages without page reload when searching names, IDs, or statuses.

---

## 3. Navigation & Information Architecture

The sidebar navigation is partitioned into 5 enterprise operational domains:

```
Rehla Admin Console
│
├── 📊 ANALYTICS & MONITORING (الرئيسية والتحليلات)
│   ├── Overview (نظرة عامة والتقارير)          → /admin/overview
│   └── Audit Trail (سجل التدقيق والرقابة)         → /admin/audit-log [MFA Protected]
│
├── ✈️ CATALOG & OPERATIONS (الخدمات والعمليات)
│   ├── Service Catalog (كتالوج الخدمات)           → /admin/services
│   ├── Application Forms (نماذج التقديم)         → /admin/application-forms
│   ├── Orders (إدارة الطلبات)                    → /admin/orders
│   └── Service Executions (مراحل التنفيذ)        → /admin/service-executions
│
├── 💳 FINANCE & TREASURY (المالية والخزينة)
│   ├── Top-up Requests (طلبات شحن الرصيد)       → /admin/top-up-requests [MFA Protected]
│   ├── Company Bank Accounts (الحسابات البنكية)  → /admin/bank-accounts
│   └── Wallets & Ledger (المحافظ وسجل العمليات)  → /admin/wallets
│
├── 👥 CRM & TRAVELERS (العملاء والمسافرين)
│   ├── Customers Directory (دليل العملاء)        → /admin/customers
│   └── Travelers Records (سجل المسافرين والوثائق) → /admin/travelers
│
└── 🛡️ GOVERNANCE & SYSTEM (النظام والإعدادات)
    ├── Content CMS (إدارة المحتوى والصفحات)       → /admin/content
    ├── Notifications (سجل الإشعارات)            → /admin/notifications
    └── Roles & Access (الأدوار والصلاحيات)       → /admin/roles-permissions [MFA Protected]
```

---

## 4. Section-by-Section CRUD & Action Matrix

### 4.1 Service Catalog (`/admin/services`)
- **List View**: Status filter (`all`, `draft`, `published`, `deactivated`), name search, formatted minor price (`SDG`).
- **Create Service (Drawer)**: English/Arabic names, short & detailed descriptions, duration, initial price in minor units (`price_minor`). Handled by `Rehla\Catalog\Actions\CreateService`.
- **Edit Content (Drawer)**: Update descriptions and expected duration without changing prices. Handled by `Rehla\Catalog\Actions\UpdateServiceContent`.
- **Change Price (Modal)**: Dedicated minor unit price update with effective validation. Handled by `Rehla\Catalog\Actions\ChangeServicePrice`.
- **Publish (Action)**: One-click publishing for validated draft services. Handled by `Rehla\Catalog\Actions\PublishService`.
- **Deactivate (Action)**: Safely deactivate an active service from public catalog. Handled by `Rehla\Catalog\Actions\DeactivateService`.

### 4.2 Company Bank Accounts (`/admin/bank-accounts`)
- **List View**: Active accounts highlighted, bank names (EN/AR), account numbers, beneficiary names.
- **Create Account (Drawer/Modal)**: Bank name EN/AR, account number, beneficiary, sort order. Handled by `Rehla\TopUps\Actions\CreateBankAccount`.
- **Edit Account (Drawer/Modal)**: Update bank names, account number, or beneficiary details. Handled by `Rehla\TopUps\Actions\UpdateBankAccount`.
- **Toggle Active / Deactivate (Action)**: Instantly deactivate/activate account for receiving customer deposits. Handled by `Rehla\TopUps\Actions\DeactivateBankAccount`.

### 4.3 Application Forms (`/admin/application-forms`)
- **List View**: Forms grouped by service, displaying active version number and status (`draft`, `published`).
- **Create / Update Draft (Drawer with Visual Field Builder)**:
  - Add/remove dynamic fields (text, number, date, select, file).
  - Configure field labels (EN/AR), validations, and required flags.
  - Handled by `Rehla\Forms\Actions\CreateFormDraft` and `UpdateFormDraft`.
- **Live Preview (Modal)**: Render the form fields as customer sees them.
- **Publish Version (Action)**: Lock schema and publish live. Handled by `Rehla\Forms\Actions\PublishFormVersion`.

### 4.4 Content CMS (`/admin/content`)
- **List View**: Content blocks organized by key and category.
- **Upsert Block (Drawer)**: Key, localized titles, localized markdown content, and publish status. Handled by `Rehla\Content\Actions\UpsertContentBlock`.

### 4.5 Roles & Permissions (`/admin/roles-permissions`)
- **Matrix View**: Visual grid displaying all 14 abilities across standard roles (`super_admin`, `operations`, `finance`).
- **Staff Assignment (Drawer)**: Select staff user, assign or revoke roles. Handled by `Rehla\Identity\Actions\AssignRole` and `RevokeRole`.
- **MFA Enforcement**: Action requires valid TOTP MFA confirmation within 12 hours.

### 4.6 Top-Up Requests (`/admin/top-up-requests`)
- **List View**: Filter by pending, approved, rejected. Displays amount in minor units (`SDG`), customer reference, payment receipt document preview.
- **Review & Approval (Modal)**: Approves request and credits wallet via `Rehla\TopUps\Actions\ApproveTopUp` (MFA protected).
- **Rejection (Modal)**: Rejects with operator reason via `Rehla\TopUps\Actions\RejectTopUp` (MFA protected).

### 4.7 Orders & Service Executions (`/admin/orders` & `/admin/service-executions`)
- **Orders View**: Order snapshot viewer, total minor amount, customer details, line items breakdown.
- **Executions Lifecycle Management**:
  - Detailed Execution Sheet with lifecycle progress bar (`draft` → `pending_documents` → `in_progress` → `completed` / `cancelled`).
  - Transition Action (`Rehla\Fulfillment\Actions\TransitionExecution`).
  - Request Customer Action (`Rehla\Fulfillment\Actions\RequestCustomerAction`).
  - Internal Operator Notes (`Rehla\Fulfillment\Actions\AddExecutionNote`).

### 4.8 Customers, Travelers, Wallets & Audit Log
- **Customers (`/admin/customers`)**: Directory view showing user status, wallet balance minor units, and registered date.
- **Travelers (`/admin/travelers`)**: Masked passport presentation (security invariant), normalized passport search, traveler profile drawer.
- **Wallets (`/admin/wallets`)**: Read-only ledger view with immutable credit/debit audit entries.
- **Audit Log (`/admin/audit-log`)**: Immutable chronological timeline of staff actions (MFA protected).

---

## 5. Architectural Boundaries & Invariant Adherence

1. **No Cross-Package Eloquent Model Imports**:
   `Rehla\Admin` must interact strictly with domain packages through:
   - Contracts (`Rehla\<Package>\Contracts\*`)
   - Data Transfer Objects (`Rehla\<Package>\Data\*`)
   - Actions (`Rehla\<Package>\Actions\*`)
   - Queries (`Rehla\<Package>\Queries\*`)
2. **Financial Precision**: All money figures formatted from integer minor units (`amount_minor / 100` SDG). Zero floats in business logic.
3. **MFA Protocol**: Sensitive endpoints (`topups.review`, `roles.manage`, `audit.view`) require active MFA within 12 hours. The layout displays a live MFA status indicator and quick confirmation modal.

---

## 6. Verification & Automated Testing Plan

1. **Architecture Rule Enforcement**:
   - `tests/Architecture/ModelBoundaryTest.php` must pass with zero violations.
   - `tests/Architecture/PackageDependencyTest.php` must pass.
2. **Feature & Integration Tests**:
   - `packages/Rehla/Admin/tests/Feature/AdminCapabilityMatrixTest.php`: Verify all abilities and MFA restrictions.
   - `packages/Rehla/Admin/tests/Feature/AdminStaffJourneyTest.php`: Verify full staff flows across the redesigned dashboard.
   - New Feature Tests for extended CRUD actions:
     - `AdminServicesCrudTest.php` (Create, Edit, Price Change, Deactivate, Publish).
     - `AdminBankAccountsCrudTest.php` (Create, Edit, Deactivate).
     - `AdminRolesAssignmentTest.php` (Assign, Revoke).
3. **Full System Verification**:
   - `composer verify` (Laravel Pint + Parallel Pest test suite).
