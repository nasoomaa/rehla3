# Rehla Platform — AI Agent Development Guidelines & Rules

> **CRITICAL INSTRUCTION FOR ALL AI AGENTS:**
> This repository contains **Rehla (رحلة)**, a strict **Modular Monolith** built on **Laravel 13.x**, **PHP 8.5+**, and **PostgreSQL 18**.
> You MUST follow all the architectural rules, boundaries, and development protocols described below without exception. Any deviation from these rules will fail the automated architecture tests (`tests/Architecture`) and will be rejected.

---

## 1. Architectural Principles & Package Boundaries

1. **Modular Monolith Structure**:
   - The system is partitioned into 19 independent packages located under `packages/Rehla/<PackageName>`.
   - The root application orchestrates packages and provides the runtime environment; all business domain logic lives strictly inside packages.

2. **Package Dependency Rules (`docs/architecture/rehla-package-map.json`)**:
   - `Core` is foundational and **MUST NOT** import any other `Rehla\` package.
   - Domain/business packages (`Identity`, `Audit`, `Catalog`, `Forms`, `Travelers`, `Documents`, `Wallet`, `TopUps`, `Orders`, `Purchasing`, `Fulfillment`, `Content`, `Notifications`, `Integrations`, `Reporting`) **MUST NOT** import presentation packages (`Web`, `Api`, `Admin`).
   - Presentation packages (`Web`, `Api`, `Admin`) must never import each other.
   - Package imports must strictly obey the whitelist defined in `docs/architecture/rehla-package-map.json`. Automated test `tests/Architecture/PackageDependencyTest.php` enforces this.

3. **Eloquent Model Boundary Invariant (Strictly Enforced)**:
   - **Eloquent models (`Rehla\<Package>\Models\*`) CANNOT be imported, type-hinted, or referenced across package boundaries.**
   - Cross-package interaction must occur exclusively through:
     - Public Contracts (`Rehla\<Package>\Contracts\*`)
     - Data Transfer Objects (`Rehla\<Package>\Data\*`)
     - Domain Actions (`Rehla\<Package>\Actions\*`)
     - Read-only Models / Queries (`Rehla\<Package>\Queries\*`)
   - Returning or passing mutable Eloquent models between packages is strictly forbidden. Automated test `tests/Architecture/ModelBoundaryTest.php` enforces this.

4. **Single-Table Ownership (`docs/architecture/table-ownership.json`)**:
   - Every database table belongs to exactly ONE package as declared in `docs/architecture/table-ownership.json`.
   - All migrations for a table must live in `packages/Rehla/<Package>/database/migrations/`.
   - Direct raw SQL queries or schema definitions targeting tables owned by other packages are prohibited. Automated test `tests/Architecture/MigrationOwnershipTest.php` enforces this.

---

## 2. Package Anatomy & Code Conventions

Every package in `packages/Rehla/<PackageName>` must adhere to the standard directory layout:

```
packages/Rehla/<PackageName>/
├── composer.json               # Package definition and dependencies
├── database/
│   └── migrations/             # Migrations for tables owned strictly by this package
├── src/
│   ├── Actions/                # Single-purpose domain action classes (final by default)
│   ├── Contracts/              # Public interfaces for cross-package use
│   ├── Data/                   # Immutable Data Transfer Objects (DTOs, readonly/final)
│   ├── Enums/                  # Backed enums for statuses, error codes, categories
│   ├── Events/                 # Domain events
│   ├── Exceptions/             # Domain-specific exceptions
│   ├── Models/                 # Eloquent models (INTERNAL ONLY — never imported outside)
│   ├── Queries/                # Read-only query services and view models
│   ├── Services/               # Internal package services
│   └── <Package>ServiceProvider.php
└── tests/
    ├── Feature/                # Integration / Feature tests (PostgreSQL)
    └── Unit/                   # Fast isolated unit tests
```

### Code Quality Standards
- `declare(strict_types=1);` at the top of every PHP file.
- Explicit typing for all class properties, method parameters, and return types.
- Actions and DTOs should be marked `final` to prevent unintended inheritance.
- Use Backed Enums (e.g. string backed) for domain states, currency, error codes.
- Primary keys must be UUIDs (`$table->uuid('id')->primary()`).
- Database JSON columns on PostgreSQL must use `jsonb`.

---

## 3. Domain & Data Invariants

1. **Financial Precision & Money Rules**:
   - The only currency supported in Phase 1 is **SDG** (Sudanese Pound).
   - Money amounts MUST be represented as integers in minor units (`amount_minor`, scale 100, e.g. 50,000 SDG = `5000000`).
   - **FLOATING POINT NUMBERS (`float`, `double`) ARE STRICTLY FORBIDDEN** for financial amounts or calculations.
   - Wallets cannot have negative balances (`balance_minor >= 0`).

2. **Immutable Append-Only Records**:
   - Financial ledger entries (`ledger_entries`), audit logs (`audit_entries`), published form schemas (`form_versions`), and order snapshots (`order_snapshots`) are immutable historical records.
   - They MUST be protected against `UPDATE` and `DELETE` statements by PostgreSQL triggers and functions. Automated integration tests verify that direct SQL updates/deletes throw a database exception.

3. **Deny-by-Default Access & Mandatory MFA**:
   - Authorization defaults to `false` (Deny-by-Default).
   - Sensitive administrative operations (`topups.review`, `roles.manage`, `audit.view`) strictly require a fresh TOTP MFA verification within a 12-hour window.
   - Complete guard isolation: the customer guard (`web`) has zero authentication access to the administrative guard (`admin`).

4. **Private Document Lifecycle**:
   - All uploaded documents are private by default and stored on private disks with randomized UUID keys (e.g. `documents/{uuid}.bin`).
   - Customer names or identifying metadata must NEVER appear in storage paths or filenames.
   - Storage keys and permanent public URLs must NEVER be leaked to clients.
   - All uploads must pass magic byte detection, file structure checks, and malware scanning before being marked `clean`.
   - File download responses must enforce security headers: `Content-Disposition: attachment`, `X-Content-Type-Options: nosniff`, and `Cache-Control: private, no-store`.

5. **Passport Number Normalization & Uniqueness**:
   - Passport numbers are normalized by converting to uppercase and stripping all Unicode whitespace and hyphens.
   - Normalized passport numbers must be globally unique across active traveler records.
   - Error messages for duplicate passports must return standard codes (`ErrorCode::DUPLICATE_PASSPORT`) without revealing who owns the conflicting record.

6. **Transactional Outbox Pattern**:
   - **ZERO external side effects (HTTP requests, emails, SMS, external APIs) inside database transactions (`DB::transaction`).**
   - Events must be recorded in `outbox_messages` within the same transaction.
   - Outbox processing must run in background workers using batch claiming with `FOR UPDATE SKIP LOCKED` and lease expiration recovery (5 minutes).

---

## 4. Strictly Forbidden Patterns (Negative Invariants)

| Forbidden Action | Correct Alternative |
| --- | --- |
| Importing `Rehla\X\Models\Foo` inside Package `Y` | Use `Rehla\X\Contracts\*`, `Rehla\X\Data\*`, or `Rehla\X\Actions\*` |
| Using `float` or `double` for currency/money | Use integer minor units (`amount_minor`, scale 100) |
| Running HTTP / Mail / SMS calls inside `DB::transaction` | Persist to `outbox_messages` table and dispatch via background worker |
| Creating a table without adding to `table-ownership.json` | Register table under its owning package in `docs/architecture/table-ownership.json` |
| Creating migrations in root `database/migrations/` for package tables | Place migration in `packages/Rehla/<Package>/database/migrations/` |
| Importing `Web`, `Api`, or `Admin` in domain packages | Keep domain packages decoupled from presentation layers |
| Running integration tests against SQLite | Use PostgreSQL with test database name ending in `_testing` |
| Leaking disk storage paths or user names in filenames | Use randomized UUID keys (`documents/{uuid}.bin`) on private disks |

---

## 5. Testing & Verification Standards

1. **Real PostgreSQL for Integration Tests**:
   - Integration and feature tests MUST run on PostgreSQL with a database name ending in `_testing` (e.g. `rehla_testing`).
   - **SQLite is strictly prohibited** for testing financial, concurrency, trigger-based, or PostgreSQL-specific features.
   - Test suite enforces this safety check via `Tests\Support\AssertsSafeTestingDatabase`.

2. **Test-Driven Development (TDD) Protocol**:
   - Every feature, fix, or capability must begin with a **failing (RED) test**.
   - Write the minimum production code needed to pass the test (**GREEN**).
   - Refactor and enforce code formatting (**REFACTOR**).
   - Verify architecture boundaries and regression safety before claiming task completion.

3. **Required Verification Commands**:
   Before declaring any task or iteration complete, you MUST execute and confirm passing output for:
   ```bash
   # 1. Full verification gate (Laravel Pint + Parallel Pest Tests)
   composer verify

   # 2. Package-level test suite
   php artisan test packages/Rehla

   # 3. Architecture constraints test suite
   php artisan test tests/Architecture

   # 4. Code style check
   ./vendor/bin/pint --test
   ```

4. **Acceptance Register Tracking**:
   - Update `docs/requirements/rehla-phase-1-acceptance.csv` with accurate test paths, test names, passing statuses, and commit hashes for completed requirements (R01 through R65).
