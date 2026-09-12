# Rehla Platform

> **Rehla Platform — Modular Monolith Architecture**

**Rehla** Platform built on **Laravel 13.x** with **PostgreSQL 18** and **PHP 8.5+**, following the strict **Modular Monolith** pattern divided into 19 packages under `packages/Rehla/*`.

---

## 🏛️ Package Architecture

Business and presentation domains are organized into independent local Composer packages subject to automated architectural tests (`tests/Architecture`):

```
packages/Rehla/
├── Core/             # The independent core: currency, identifiers, time, public contracts (no external dependencies)
├── Identity/         # Accounts, employee roles, fine-grained permissions, MFA policy, session isolation
├── Audit/            # Immutable audit trail (PostgreSQL Triggers)
├── Catalog/          # Service catalog and pricing categories
├── Forms/            # Dynamic form engine and immutable published form versions
├── Travelers/        # Traveler profiles and global passport normalization with ownership isolation
├── Documents/        # Private document lifecycle: isolation, malware scanning, path protection
├── Wallet/           # Financial wallet and double-entry ledger with negative balance prevention
├── TopUps/           # Bank transfer top-up requests, receipt review, and atomic approval
├── Orders/           # Commercial orders and immutable snapshots
├── Purchasing/       # Atomic purchase engine: balance check, wallet reservation, and confirmation
├── Fulfillment/      # Service fulfillment state machine and employee assignment
├── Content/          # Translated content blocks and service guidelines
├── Notifications/    # In-app notifications and transactional outbox
├── Integrations/     # External service integrations (WhatsApp inquiry links, etc.)
├── Reporting/        # Performance indicators and dashboards
├── Web/              # Public web interface and customer portal (Blade + Livewire)
├── Api/              # REST API v1 (Laravel Sanctum)
└── Admin/            # Operations and management control panel (Filament / Custom Controls)
```

---

## ⚖️ Non-Negotiable Invariants

Every developer and AI agent working on this project must strictly adhere to the following rules without exception:

1. **Package Boundaries and Dependencies (`rehla-package-map.json`)**:
   - `Core` package is completely independent and does not depend on any other package within the project.
   - Business domains packages must not depend on presentation packages (`Web`, `Api`, `Admin`).
   - Strictly prohibited to import packages in a manner that violates the map defined in `docs/architecture/rehla-package-map.json`.

2. **Model Boundary Isolation**:
   - Eloquent models belonging to a package (`Rehla\<Package>\Models\*`) are **prohibited from being imported or used outside their package**.
   - Communication between packages must be exclusively through `Contracts`, `Data Transfer Objects (DTOs)`, `Actions`, or custom read models.

3. **Single Table Ownership (`table-ownership.json`)**:
   - Each table in the database belongs to exactly one package and is explicitly declared in `docs/architecture/table-ownership.json`.
   - Creating any migration outside the package owner's path is strictly prohibited.

4. **Append-only & Immutability**:
   - The ledger entries table (`ledger_entries`), audit trail (`audit_entries`), order snapshots (`order_snapshots`), and published form versions (`form_versions`) are historical immutable records.
   - Updating or deleting them programmatically is strictly prohibited and protected by PostgreSQL triggers and functions that raise an exception immediately upon any `UPDATE` or `DELETE` attempt.

5. **Financial Precision & Money Rules**:
   - The only currency supported is Sudanese Pound (`SDG`).
   - Monetary amounts are represented as integers in minor units (`amount_minor`, scale 100), and using decimal numbers (`float`) is strictly prohibited.
   - Negative balances are not allowed in the wallet.

6. **Deny-by-Default & MFA**:
   - Default stance for all security checks is **deny**. Explicit grants are required.
   - Sensitive operations (top-up review, role management, audit viewing, employee onboarding) require recent MFA confirmation within a 12-hour window.
   - Strict session isolation between customer (`web`) and employee/admin sessions.

7. **Private Document Lifecycle**:
   - Documents are stored on a private disk with random UUID names, and storage path leakage or permanent public URL generation is prohibited.
   - Magic bytes check and malware scanning are mandatory before attaching a document.
   - Downloads are protected with strict security headers (`Content-Disposition: attachment`, `nosniff`, `private cache`).

8. **Transactional Outbox**:
   - No external network calls or side effects are allowed inside database transactions.
   - Events must be written to the `outbox_messages` table within the same transaction and processed by a separate background worker via `ClaimOutboxBatch` using `FOR UPDATE SKIP LOCKED`.

---

## 🚀 متطلبات التشغيل والإعداد (Prerequisites & Setup)

### المتطلبات الأساسية
- **PHP**: 8.5 أو أحدث (مع إضافات `pdo_pgsql`, `mbstring`, `bcmath`, `xml`).
- **PostgreSQL**: الإصدار 18 أو أحدث مع تفعيل امتداد `citext`.
- **Composer**: 2.8 أو أحدث.
- **Node.js**: 24+ مع **npm**.

### الإعداد الأولي
```bash
# تثبيت الاعتماديات
composer install
npm ci

# إعداد البيئة ومفتاح التطبيق
cp .env.example .env
php artisan key:generate

# بناء الأصول الأمامية
npm run build
```

---

## 🧪 الاختبارات وبوابات الجودة (Verification & Testing)

تخضع كافة التغييرات لبوابات فحص صارمة قبل الاعتماد:

```bash
# تشغيل بوابة التحقق الكاملة (Pint + Parallel Tests)
composer verify

# تشغيل اختبارات معمارية الحزم والحدود
php artisan test tests/Architecture

# تشغيل اختبارات جميع حزم رحلة
php artisan test packages/Rehla

# فحص التنسيق البرمجي والتزامه بمعايير Laravel Pint
./vendor/bin/pint --test
```

> **تنبيه:** تشترط اختبارات التكامل بيئة PostgreSQL حقيقية بقاعدة بيانات ينتهي اسمها بـ `_testing` (مثل `rehla_testing`). يُمنع استخدام SQLite لاختبارات التكامل المعتمدة على الـ Triggers والأقفال والتزامن.

---

## 📚 المراجع والمستندات التفصيلية

- **مواصفات المعمارية والحزم:** [`docs/REHLA-LARAVEL-PACKAGE-ARCHITECTURE.md`](docs/REHLA-LARAVEL-PACKAGE-ARCHITECTURE.md)
- **مفهوم المنتج ورحلة المستخدم (R01–R65):** [`docs/REHLA-PROJECT-CONCEPT-AND-USER-JOURNEY.md`](docs/REHLA-PROJECT-CONCEPT-AND-USER-JOURNEY.md)
- **خريطة اعتماديات الحزم:** [`docs/architecture/rehla-package-map.json`](docs/architecture/rehla-package-map.json)
- **سجل ملكية الجداول:** [`docs/architecture/table-ownership.json`](docs/architecture/table-ownership.json)
- **قرارات التصميم المعماري (ADRs):** [`docs/adr/`](docs/adr/)
- **سجل القبول الذري للمتطلبات:** [`docs/requirements/rehla-phase-1-acceptance.csv`](docs/requirements/rehla-phase-1-acceptance.csv)
- **خطط التنفيذ المرحلية:** [`docs/superpowers/plans/`](docs/superpowers/plans/)
