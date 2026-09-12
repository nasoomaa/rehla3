# Rehla Platform (منصة رحلة) — Claude Code Guidelines

> **CRITICAL ARCHITECTURAL DIRECTIVE:**
> Rehla is a strict **Modular Monolith** built on **Laravel 13.x**, **PHP 8.5+**, and **PostgreSQL 18**.
> All business domain logic resides strictly within 19 modular packages in `packages/Rehla/<PackageName>`.
> You MUST adhere to all architectural boundaries, invariants, and testing standards defined in this document without exception. Any deviation will fail automated architectural tests (`tests/Architecture`) and will be rejected.

---

## 1. Quick Reference Commands

### Testing & Verification
```bash
# Full verification gate (Pint style check + Parallel Pest test suite)
composer verify

# Run all Rehla package tests
php artisan test packages/Rehla

# Run architecture constraint tests (enforces package boundaries, model isolation, table ownership)
php artisan test tests/Architecture

# Run a specific package test file
php artisan test packages/Rehla/Identity/tests/Feature/MfaPolicyTest.php

# Run tests with filter
php artisan test --filter=MfaPolicyTest
```

### Code Style & Linting
```bash
# Check code style (Laravel Pint)
./vendor/bin/pint --test

# Automatically fix code style violations
./vendor/bin/pint
```

### Database & Environment
```bash
# Run migrations across all packages
php artisan migrate

# Refresh test database (PostgreSQL required)
php artisan migrate:fresh --database=pgsql_testing
```

---

## 2. Architectural Invariants (The Non-Negotiables)

### A. Package Dependency Whitelist (`docs/architecture/rehla-package-map.json`)
- The system is partitioned into 19 independent packages under `packages/Rehla/`.
- **`Core` is foundational and MUST NOT import any other `Rehla\` package.**
- **Domain/Business packages MUST NEVER import presentation packages (`Web`, `Api`, `Admin`).**
- Every cross-package import must strictly match the permitted whitelist in `docs/architecture/rehla-package-map.json`.
- Enforced by: `tests/Architecture/PackageDependencyTest.php`.

### B. Eloquent Model Boundary Isolation (Strictly Enforced)
- **Eloquent models (`Rehla\<Package>\Models\*`) CANNOT be imported, type-hinted, or referenced across package boundaries.**
- Inter-package communication must occur exclusively via:
  - **Contracts** (`Rehla\<Package>\Contracts\*`)
  - **Data Transfer Objects (DTOs)** (`Rehla\<Package>\Data\*`)
  - **Domain Actions** (`Rehla\<Package>\Actions\*`)
  - **Read Queries** (`Rehla\<Package>\Queries\*`)
- Returning mutable Eloquent models from actions or service methods to other packages is strictly prohibited.
- Enforced by: `tests/Architecture/ModelBoundaryTest.php`.

### C. Single-Table Ownership (`docs/architecture/table-ownership.json`)
- Every database table belongs to exactly **ONE** owning package as declared in `docs/architecture/table-ownership.json`.
- Migrations creating or altering a table must live inside that owning package: `packages/Rehla/<Package>/database/migrations/`.
- Direct queries or raw schema modifications targeting tables owned by another package are prohibited.
- Enforced by: `tests/Architecture/MigrationOwnershipTest.php`.

### D. Append-Only Immutability via PostgreSQL Triggers
- Financial ledger entries (`ledger_entries`), audit entries (`audit_entries`), order snapshots (`order_snapshots`), and published form versions (`form_versions`) are immutable records.
- They MUST be guarded against `UPDATE` and `DELETE` queries by PostgreSQL trigger functions.
- Integration tests must verify that direct SQL updates or deletes raise a database exception.

### E. Financial Precision & Money Rules
- The only currency supported in Phase 1 is **SDG** (Sudanese Pound).
- All monetary amounts MUST be represented as integers in minor units (`amount_minor`, scale 100, e.g. 50,000 SDG = `5000000`).
- **FLOATING POINT NUMBERS (`float`, `double`) ARE STRICTLY FORBIDDEN** for monetary calculations or storage.
- Wallets cannot carry negative balances (`balance_minor >= 0`).

### F. Deny-by-Default Access & Mandatory MFA
- Authorization policies default to `false` (Deny-by-Default).
- Sensitive administrative operations (`topups.review`, `roles.manage`, `audit.view`) strictly require a fresh TOTP MFA verification within a 12-hour window.
- Absolute guard isolation: customer guard (`web`) has zero authentication access to administrative guard (`admin`).

### G. Private Document Lifecycle
- Uploaded files are private by default, stored on private disks with randomized UUID keys (e.g. `documents/{uuid}.bin`).
- Customer names or identifying metadata must NEVER appear in file paths or filenames.
- Storage paths and permanent public URLs must NEVER be leaked to clients.
- All uploads must pass magic byte detection, file structure validation, and virus scanning before being marked `clean`.
- Download responses must include security headers: `Content-Disposition: attachment`, `X-Content-Type-Options: nosniff`, and `Cache-Control: private, no-store`.

### H. Passport Number Normalization & Uniqueness
- Passport numbers are normalized: converted to uppercase, Unicode whitespace and hyphens stripped.
- Normalized passport numbers must be globally unique across active traveler records.
- Duplicate passport errors must return standard generic codes (`ErrorCode::DUPLICATE_PASSPORT`) without disclosing the owner of the existing record.

### I. Transactional Outbox Pattern
- **ZERO external side effects (HTTP calls, emails, SMS, external APIs) inside database transactions (`DB::transaction`).**
- Side effects must be recorded as events in `outbox_messages` within the transaction.
- Outbox processing runs in background workers using batch claiming with `FOR UPDATE SKIP LOCKED` and lease timeout recovery (5 minutes).

---

## 3. Package Anatomy & Code Conventions

Every package in `packages/Rehla/<PackageName>` follows this structure:

```
packages/Rehla/<PackageName>/
├── composer.json               # Package definition
├── database/
│   └── migrations/             # Migrations for tables owned by this package
├── src/
│   ├── Actions/                # Single-purpose domain actions (final classes)
│   ├── Contracts/              # Public interfaces for cross-package use
│   ├── Data/                   # Data Transfer Objects (DTOs, readonly/final)
│   ├── Enums/                  # Backed enums (e.g. status, error codes)
│   ├── Events/                 # Domain events
│   ├── Exceptions/             # Domain-specific exceptions
│   ├── Models/                 # Eloquent models (INTERNAL ONLY — never export)
│   ├── Queries/                # Read-only query services and view models
│   ├── Services/               # Internal package services
│   └── <Package>ServiceProvider.php
└── tests/
    ├── Feature/                # Integration / Feature tests (PostgreSQL)
    └── Unit/                   # Fast isolated unit tests
```

### Code Quality Standards
- `declare(strict_types=1);` at the beginning of every PHP file.
- Strict type hints on all method parameters, return types, and class properties.
- Use `final` on Actions and DTOs by default.
- Use Backed Enums for fixed sets (status, types, currencies).
- Primary keys must be UUIDs (`$table->uuid('id')->primary()`).
- JSON columns on PostgreSQL must use `jsonb`.

---

## 4. Testing & Development Protocol (TDD)

1. **Real PostgreSQL Required**:
   - Tests MUST run against PostgreSQL with a database name ending in `_testing` (e.g., `rehla_testing`).
   - SQLite is STRICTLY FORBIDDEN for tests touching triggers, concurrency, transactions, or financial logic.
2. **Red-Green-Refactor**:
   - Write a failing test first demonstrating the new requirement or bug fix.
   - Write the minimum production code needed to pass the test.
   - Refactor and run `./vendor/bin/pint` to maintain style.
3. **Acceptance Register**:
   - Update `docs/requirements/rehla-phase-1-acceptance.csv` with verified test paths, test names, passing status, and commit hashes for every completed atomic requirement.
4. **Verification Gate**:
   - Always execute `composer verify` before declaring any task complete. Both Pint and Pest must pass with zero errors.
