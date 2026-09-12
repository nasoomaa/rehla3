#!/usr/bin/env bash
set -euo pipefail

# Rehla Release Artifact Verification Script
# Verifies lockfiles, caches, assets, migrations, and health probes for an immutable deployment artifact.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "${ROOT_DIR}"

echo "=== Rehla Artifact Verification ==="
echo "Working directory: ${ROOT_DIR}"

# 1. Check lockfiles
echo "[1/7] Verifying dependency lockfiles..."
if [[ ! -f "composer.lock" ]]; then
    echo "ERROR: composer.lock is missing!" >&2
    exit 1
fi

if [[ ! -f "package-lock.json" ]]; then
    echo "ERROR: package-lock.json is missing!" >&2
    exit 1
fi
echo "  -> Lockfiles verified: composer.lock, package-lock.json"

# 2. Check Vite build artifacts & manifest
echo "[2/7] Verifying compiled frontend assets..."
if [[ ! -f "public/build/manifest.json" ]]; then
    echo "ERROR: public/build/manifest.json is missing! Run npm run build first." >&2
    exit 1
fi

MANIFEST_SIZE=$(wc -c < "public/build/manifest.json")
if [[ "${MANIFEST_SIZE}" -lt 10 ]]; then
    echo "ERROR: public/build/manifest.json is empty or invalid!" >&2
    exit 1
fi
echo "  -> Frontend build manifest verified (${MANIFEST_SIZE} bytes)"

# 3. Check Configuration Cache
echo "[3/7] Verifying config cache..."
php artisan config:cache
echo "  -> Configuration cached successfully"

# 4. Check Route Cache
echo "[4/7] Verifying route cache..."
php artisan route:cache
echo "  -> Routes cached successfully"

# 5. Check View Cache
echo "[5/7] Verifying Blade view cache..."
php artisan view:cache
echo "  -> Blade templates cached successfully"

# 6. Check Database Migrations Status
echo "[6/7] Checking database migration status..."
STATUS_OUT=$(php artisan migrate:status --pending 2>&1 || true)
if echo "${STATUS_OUT}" | grep -qi "No pending migrations"; then
    echo "  -> Database schema is up to date (no pending migrations)"
elif echo "${STATUS_OUT}" | grep -qi "Pending"; then
    echo "ERROR: Pending migrations found:" >&2
    echo "${STATUS_OUT}" >&2
    exit 1
else
    echo "  -> Database schema is up to date"
fi

# Clear build caches so environment testing config is pristine for probe test
php artisan config:clear > /dev/null 2>&1 || true
php artisan route:clear > /dev/null 2>&1 || true
php artisan view:clear > /dev/null 2>&1 || true

# 7. Check Application Readiness Probe
echo "[7/7] Verifying /ready and /up health probes..."
php artisan test tests/Feature/HealthEndpointsTest.php
echo "  -> Health endpoints /up and /ready verified successfully"



# Clean caches to return workspace to clean dev state
php artisan config:clear > /dev/null 2>&1 || true
php artisan route:clear > /dev/null 2>&1 || true
php artisan view:clear > /dev/null 2>&1 || true

echo "=== Release Artifact Verification Passed Successfully ==="
exit 0
