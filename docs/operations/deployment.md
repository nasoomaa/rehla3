# Rehla Production Deployment & Migration Policy

## 1. Single Immutable Artifact
Rehla builds immutable deployment artifacts from a single Git commit SHA. The release artifact is verified prior to deployment via `scripts/release/verify-artifact.sh`:
- Dependency lockfiles (`composer.lock`, `package-lock.json`) are frozen and validated.
- Production assets are compiled ahead-of-time with Vite (`public/build/manifest.json`). No compilers or build tools run on production nodes.
- Framework caches (`config:cache`, `route:cache`, `view:cache`) are generated.
- Database migration status is validated (`php artisan migrate:status --pending`).
- Application readiness probe (`/ready`) passes with 200 OK across PostgreSQL, private disk, and cache.

---

## 2. Zero-Downtime Deployment Lifecycle
Production releases follow a blue/green or rolling symlink switch:
1. **Pre-flight**:
   - Run `bash scripts/release/verify-artifact.sh` on release artifact.
   - Run automated backup `bash scripts/release/backup.sh`.
2. **Schema Phase (Expand)**:
   - Execute forward additive migrations only: `php artisan migrate --force`.
   - Never drop columns, rename tables, or make non-nullable constraints without defaults in the initial migration.
3. **Traffic Switch**:
   - Point traffic to new container/release directory.
   - Workers restarted gracefully: `php artisan queue:restart`.
4. **Post-Deploy Validation**:
   - Confirm `/up` and `/ready` return 200 OK.
   - Monitor error rate (< 2% threshold) and outbox processing.

---

## 3. Expand / Backfill / Contract Migration Protocol

Any breaking or structural schema change MUST be split into three distinct releases to prevent downtime and data loss:

### Phase 1: Expand (Additive Schema)
- Add new columns or tables as nullable or with safe defaults.
- Deploy new application code that performs **dual-writes** (writes to both old and new schema) while continuing to read from the old schema (or new with fallback).
- Old application code remains fully operational during rolling deployment.

### Phase 2: Backfill (Resumable Cursor-Based Migration)
- Backfill legacy records using cursor-based batch processing with idempotency:
  ```bash
  php artisan rehla:backfill --chunk=1000
  ```
- Backfill is interruptible, resumable, and logs progress with checksum and count verification.
- Immutability invariant: historical tables (`ledger_entries`, `audit_entries`, `orders`, `form_versions`) must never have existing rows altered in place; new projection tables or additive columns only.

### Phase 3: Contract (Cleanup)
- Once backfill is 100% verified and all running instances use the new schema, switch reads entirely to the new schema.
- In a subsequent release, remove legacy columns or temporary dual-write paths.
- **NEVER execute destructive `down()` migrations on production tables.**

---

## 4. Rollback and Disaster Protocols
- Since migrations are forward-compatible and additive (Expand), rollbacks of application code do NOT require reverting database schema.
- In the event of application regression, traffic is immediately switched back to previous release artifact symlink.
- If catastrophic database corruption occurs, execute point-in-time recovery via `scripts/release/restore-rehearsal.sh`.
