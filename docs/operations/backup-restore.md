# Rehla Backup & Disaster Recovery Runbook

## 1. Objectives & SLAs
- **RPO (Recovery Point Objective)**: <= 15 minutes.
  - Continuous PostgreSQL WAL archiving paired with consistent snapshots.
- **RTO (Recovery Time Objective)**: <= 4 hours.
  - Automated restoration script into isolated target environment.

---

## 2. Backup Architecture & Manifest Coordination
A consistent backup requires coordinating database state and private blob storage:
1. **Timestamp Synchronization**:
   - Single UTC ISO timestamp serves as the anchor for both database dump and blob storage manifest.
2. **Database Snapshot & WAL Tracking**:
   - PostgreSQL physical or logical snapshot (`pg_dump -Fc` or volume snapshot).
   - Capture current Write-Ahead Log (WAL) LSN via `SELECT pg_current_wal_lsn();`.
3. **Private Storage Manifest (`blobs-manifest.json`)**:
   - Inventory of all files in private document storage (`storage/app/private`).
   - Recorded fields per blob:
     - `storage_key`
     - `sha256` checksum
     - `size_bytes`
     - `created_at`
     - Database reference count in `documents` table.

---

## 3. Automated Scripts

### Taking a Backup
```bash
bash scripts/release/backup.sh
```
Produces an archive directory `/tmp/rehla_backups/{timestamp}/` containing:
- `database.dump`: Binary PostgreSQL dump.
- `database-wal.json`: WAL location metadata.
- `blobs-manifest.json`: Checksum inventory of all private storage objects.
- `backup-manifest.json`: Top-level signed manifest with verification checksums.

### Restoring and Rehearsal Drill
```bash
bash scripts/release/restore-rehearsal.sh
```
Executes a rehearsal drill in an isolated verification sandbox:
1. Restores database into isolated test schema or staging database (`rehla_restore_rehearsal`).
2. Measures elapsed restore time against RTO target (fails if > 14,400 seconds).
3. Verifies RPO gap between backup timestamp and last transaction (fails if > 900 seconds).
4. Runs integrity validation suite:
   - Row count parity across all tables.
   - Zero wallet balance mismatches: `balance_minor = sum(credits) - sum(debits)`.
   - Outbox processing state verification.
   - Blob checksum audit matching `blobs-manifest.json`.
   - Sample authorized document read/download verification.
5. Emits structured JSON summary to `restore-rehearsal-report.json`.
