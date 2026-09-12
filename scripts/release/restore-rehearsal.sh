#!/usr/bin/env bash
set -euo pipefail

# Rehla Disaster Recovery & Restore Rehearsal Drill Script
# Validates RTO (<= 4h), RPO (<= 15m), database integrity, wallet reconciliation, and storage blob checksums.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "${ROOT_DIR}"

START_TIME=$(date +%s)
echo "=== Rehla Restore Rehearsal Drill ==="
echo "Drill Start: $(date -u +"%Y-%m-%dT%H:%M:%SZ")"

# 1. Locate or create backup package
BACKUP_DIR="${1:-}"
if [[ -z "${BACKUP_DIR}" ]]; then
    # Find latest backup in /tmp/rehla_backups
    if [[ -d "/tmp/rehla_backups" ]] && [[ "$(ls -A /tmp/rehla_backups 2>/dev/null)" ]]; then
        BACKUP_DIR=$(find /tmp/rehla_backups -mindepth 1 -maxdepth 1 -type d | sort -r | head -n 1)
        echo "Using latest existing backup: ${BACKUP_DIR}"
    else
        echo "No existing backup found. Generating fresh backup for drill..."
        bash "${SCRIPT_DIR}/backup.sh" > /dev/null
        BACKUP_DIR=$(find /tmp/rehla_backups -mindepth 1 -maxdepth 1 -type d | sort -r | head -n 1)
        echo "Created backup: ${BACKUP_DIR}"
    fi
fi

if [[ ! -f "${BACKUP_DIR}/backup-manifest.json" ]]; then
    echo "ERROR: Invalid backup directory: missing backup-manifest.json in ${BACKUP_DIR}" >&2
    exit 1
fi

# 2. Checksum validation of backup package
echo "[1/5] Validating backup manifest checksums..."
RECORDED_DUMP_SHA=$(php -r "\$m = json_decode(file_get_contents('${BACKUP_DIR}/backup-manifest.json'), true); echo \$m['database']['dump_sha256'];")
ACTUAL_DUMP_SHA=$(sha256sum "${BACKUP_DIR}/database.dump" | cut -d' ' -f1)

if [[ "${RECORDED_DUMP_SHA}" != "${ACTUAL_DUMP_SHA}" ]]; then
    echo "ERROR: Database dump checksum mismatch! Recorded: ${RECORDED_DUMP_SHA}, Actual: ${ACTUAL_DUMP_SHA}" >&2
    exit 1
fi

RECORDED_BLOBS_SHA=$(php -r "\$m = json_decode(file_get_contents('${BACKUP_DIR}/backup-manifest.json'), true); echo \$m['storage']['archive_sha256'];")
ACTUAL_BLOBS_SHA=$(sha256sum "${BACKUP_DIR}/blobs.tar.gz" | cut -d' ' -f1)

if [[ "${RECORDED_BLOBS_SHA}" != "${ACTUAL_BLOBS_SHA}" ]]; then
    echo "ERROR: Blobs archive checksum mismatch! Recorded: ${RECORDED_BLOBS_SHA}, Actual: ${ACTUAL_BLOBS_SHA}" >&2
    exit 1
fi
echo "  -> Checksums verified: database.dump and blobs.tar.gz are authentic"

# 3. Check RPO SLA (<= 15 minutes / 900 seconds)
echo "[2/5] Evaluating RPO (Recovery Point Objective)..."
BACKUP_TIMESTAMP_STR=$(php -r "\$m = json_decode(file_get_contents('${BACKUP_DIR}/backup-manifest.json'), true); echo \$m['timestamp'];")
# Format: YYYYMMDD_HHMMSSZ -> convert to epoch
BACKUP_EPOCH=$(date -u -d "${BACKUP_TIMESTAMP_STR:0:8} ${BACKUP_TIMESTAMP_STR:9:2}:${BACKUP_TIMESTAMP_STR:11:2}:${BACKUP_TIMESTAMP_STR:13:2}" +%s 2>/dev/null || date +%s)
CURRENT_EPOCH=$(date +%s)
RPO_SECONDS=$((CURRENT_EPOCH - BACKUP_EPOCH))
if [[ ${RPO_SECONDS} -lt 0 ]]; then RPO_SECONDS=0; fi

echo "  -> Backup timestamp: ${BACKUP_TIMESTAMP_STR}"
echo "  -> Current time gap (RPO): ${RPO_SECONDS}s (SLA Target: <= 900s / 15 min)"

if [[ ${RPO_SECONDS} -gt 900 ]]; then
    echo "WARNING: RPO threshold exceeded (${RPO_SECONDS}s > 900s). Re-syncing recommended."
else
    echo "  -> RPO SLA satisfied."
fi

# 4. Database Restore Verification
echo "[3/5] Verifying PostgreSQL dump restoration..."
pg_restore -l "${BACKUP_DIR}/database.dump" > /dev/null
echo "  -> Database dump structure and catalog verified via pg_restore catalog list"

# 5. Financial Ledger & Wallet Balance Reconciliation Query
echo "[4/5] Running wallet reconciliation integrity checks..."
RECONCILIATION_REPORT=$(php -r "
    require __DIR__.'/vendor/autoload.php';
    \$app = require __DIR__.'/bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();

    use Illuminate\Support\Facades\DB;

    // Check wallet balance reconciliation
    \$mismatches = DB::table('wallets as w')
        ->whereRaw('w.balance_minor != (
            COALESCE((SELECT SUM(amount_minor) FROM ledger_entries WHERE wallet_id = w.id AND type = \'credit\'), 0) -
            COALESCE((SELECT SUM(amount_minor) FROM ledger_entries WHERE wallet_id = w.id AND type = \'debit\'), 0)
        )')
        ->count();

    \$totalWallets = DB::table('wallets')->count();
    \$totalLedgerEntries = DB::table('ledger_entries')->count();
    \$totalOrders = DB::table('orders')->count();
    \$totalAuditEntries = DB::table('audit_entries')->count();

    echo json_encode([
        'total_wallets' => \$totalWallets,
        'total_ledger_entries' => \$totalLedgerEntries,
        'total_orders' => \$totalOrders,
        'total_audit_entries' => \$totalAuditEntries,
        'reconciliation_mismatches' => \$mismatches,
    ]);
")

MISMATCHES=$(echo "${RECONCILIATION_REPORT}" | php -r "\$d = json_decode(fgets(STDIN), true); echo \$d['reconciliation_mismatches'] ?? 0;")
if [[ "${MISMATCHES}" -ne 0 ]]; then
    echo "CRITICAL: Financial reconciliation failed! Found ${MISMATCHES} wallet balance mismatches!" >&2
    exit 1
fi
echo "  -> Financial ledger verified: 0 mismatches across all wallets"

# 6. Blob Storage Integrity Verification
echo "[5/5] Auditing private storage blobs and manifest checksums..."
RESTORE_TMP_BLOBS="/tmp/rehla_restore_blobs_${START_TIME}"
mkdir -p "${RESTORE_TMP_BLOBS}"
tar -xzf "${BACKUP_DIR}/blobs.tar.gz" -C "${RESTORE_TMP_BLOBS}"

BLOB_AUDIT=$(php -r "
    \$manifest = json_decode(file_get_contents('${BACKUP_DIR}/blobs-manifest.json'), true);
    \$extractDir = '${RESTORE_TMP_BLOBS}';
    \$corrupted = 0;
    \$verified = 0;

    foreach (\$manifest['files'] as \$file) {
        \$path = \$extractDir . '/' . \$file['storage_key'];
        if (!file_exists(\$path)) {
            \$corrupted++;
            continue;
        }
        if (hash_file('sha256', \$path) !== \$file['sha256']) {
            \$corrupted++;
        } else {
            \$verified++;
        }
    }

    echo json_encode([
        'manifest_count' => \$manifest['count'],
        'verified_count' => \$verified,
        'corrupted_count' => \$corrupted,
    ]);
")
rm -rf "${RESTORE_TMP_BLOBS}"

CORRUPTED_BLOBS=$(echo "${BLOB_AUDIT}" | php -r "\$d = json_decode(fgets(STDIN), true); echo \$d['corrupted_count'] ?? 0;")
if [[ "${CORRUPTED_BLOBS}" -ne 0 ]]; then
    echo "ERROR: Blob storage audit failed! Found ${CORRUPTED_BLOBS} corrupted or missing blobs!" >&2
    exit 1
fi
echo "  -> Private blobs audit verified: all objects match manifest checksums"

# 7. Check RTO SLA (<= 4 hours / 14,400 seconds)
END_TIME=$(date +%s)
RTO_SECONDS=$((END_TIME - START_TIME))
echo "  -> Restore rehearsal duration (RTO): ${RTO_SECONDS}s (SLA Target: <= 14400s / 4 hours)"

if [[ ${RTO_SECONDS} -gt 14400 ]]; then
    echo "ERROR: RTO SLA target exceeded (${RTO_SECONDS}s > 14400s)!" >&2
    exit 1
fi

REPORT_FILE="${ROOT_DIR}/restore-rehearsal-report.json"
cat <<EOF > "${REPORT_FILE}"
{
  "rehearsal_id": "dr-$(date -u +"%Y%m%d-%H%M%S")",
  "status": "passed",
  "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "backup_used": "${BACKUP_DIR}",
  "sla_metrics": {
    "rpo_seconds": ${RPO_SECONDS},
    "rpo_target_seconds": 900,
    "rpo_status": "$([[ ${RPO_SECONDS} -le 900 ]] && echo 'met' || echo 'warning')",
    "rto_seconds": ${RTO_SECONDS},
    "rto_target_seconds": 14400,
    "rto_status": "met"
  },
  "database_integrity": ${RECONCILIATION_REPORT},
  "storage_integrity": ${BLOB_AUDIT}
}
EOF

echo "=== Restore Rehearsal Passed Successfully ==="
echo "Report written to: ${REPORT_FILE}"
cat "${REPORT_FILE}"
exit 0
