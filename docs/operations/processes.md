# Rehla Operations Runbook — Process Topology & Lifecycle

## 1. Process Architecture

The Rehla platform runtime consists of three primary containerized process types:

```
┌─────────────────────────────────────────────────────────────┐
│                       Rehla Cluster                         │
│                                                             │
│   ┌───────────────┐   ┌────────────────┐   ┌────────────┐   │
│   │  Web (HTTP)   │   │  Queue Worker  │   │ Scheduler  │   │
│   │  nginx/php-fpm│   │   queue:work   │   │schedule:run│   │
│   └───────┬───────┘   └───────┬────────┘   └─────┬──────┘   │
│           │                   │                  │          │
│           ▼                   ▼                  ▼          │
│     PostgreSQL 18       Redis / Cache      Private Storage  │
└─────────────────────────────────────────────────────────────┘
```

### Process 1: Web HTTP Frontend
- **Command**: `php artisan serve` (Dev) / `php-fpm` (Production)
- **Liveness Probe**: `GET /up` (HTTP 200 `{"status": "alive"}`)
  - Validates container process responsiveness.
  - Zero external database or storage dependencies.
- **Readiness Probe**: `GET /ready` (HTTP 200 `{"status": "ready"}`)
  - Validates PostgreSQL connection via `SELECT 1`.
  - Validates private disk filesystem metadata operations without writing customer data.
  - Validates application cache store.
  - Traffic router routes traffic only when `/ready` returns HTTP 200.

### Process 2: Background Queue Workers
- **Command**: `php artisan queue:work --timeout=90 --tries=1`
- **Concurrency & Lease Policy**:
  - `retry_after`: 120 seconds (strictly greater than `--timeout=90`).
  - Worker lease expiration: 5 minutes (300 seconds).
  - Outbox workers query pending messages using batch claiming with `FOR UPDATE SKIP LOCKED`.
- **Worker Restarts**:
  - Signal: `SIGTERM` triggers graceful shutdown, completing currently claimed job before exiting.
  - Periodic restart: Worker exits automatically after memory threshold or max jobs to prevent leaks.

### Process 3: Cron Scheduler
- **Command**: `php artisan schedule:run`
- **Execution**: Every 1 minute (`* * * * *`).
- **Singleton Lock Protection**:
  - High-value jobs (`rehla:outbox-worker`, `rehla:recover-expired-leases`, `rehla:reconcile-wallets`) mandate:
    - `->withoutOverlapping()`
    - `->onOneServer()`
  - Uses atomic cache lock mutexes to eliminate race conditions across multiple scheduler instances.

---

## 2. Worker Restart & Lease Recovery Procedures

1. If a worker process crashes mid-transaction:
   - The PostgreSQL row-level lock held during `FOR UPDATE SKIP LOCKED` is immediately released by PostgreSQL connection termination.
   - Any message in `processing` state whose lease timestamp exceeds 5 minutes is automatically recovered by `rehla:recover-expired-leases`.
2. To trigger a rolling restart of all queue workers:
   ```bash
   php artisan queue:restart
   ```
