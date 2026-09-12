# Rehla Operations Runbook — Production Monitoring & Alerting Matrix

## 1. Alert Severity Definitions

| Severity | Definition | Response Target | Escalation Channel |
| --- | --- | --- | --- |
| **P0 (Critical)** | Direct customer financial impact, ledger imbalance, data loss risk | Immediate (< 15 min) | PagerDuty / On-call Voice |
| **P1 (High)** | Degradation of fulfillment, stalled outbox, elevated error budget burn | < 30 min | Slack #ops-alerts & SMS |
| **P2 (Medium)** | Queue backlog, review latency, non-blocking operational delays | < 4 hours | Slack #ops-monitoring |
| **P3 (Low)** | Informational notices, scheduled maintenance window updates | Business Hours | Email Digest |

---

## 2. Production Alert Trigger Matrix

### P0: Financial Reconciliation Mismatch
- **Metric**: `rehla.wallet.reconciliation_mismatch_count`
- **Threshold**: `> 0`
- **Condition**: Discrepancy between customer wallet balance and sum of ledger entries.
- **Runbook**:
  1. Freeze affected wallet withdrawals and debits immediately.
  2. Inspect audit entries and ledger records for the affected `wallet_id`.
  3. Verify PostgreSQL trigger protection on `ledger_entries`.
  4. Perform atomic manual adjustment through authorized administrative correction action with dual audit trail.

### P1: Stalled Transactional Outbox
- **Metric**: `rehla.outbox.oldest_message_age_seconds`
- **Threshold**: `> 300 seconds (5 minutes)`
- **Condition**: Outbox dispatcher has stopped consuming or processing notifications.
- **Runbook**:
  1. Inspect worker health: `php artisan queue:monitor`.
  2. Check for stuck locks in `outbox_messages` table:
     ```sql
     SELECT id, status, attempts, lease_expires_at FROM outbox_messages WHERE status = 'processing' AND lease_expires_at < NOW();
     ```
  3. Execute manual lease recovery: `php artisan rehla:recover-expired-leases`.
  4. If workers are unresponsive, restart worker pool: `php artisan queue:restart`.

### P1: Dead Letter Messages Detected
- **Metric**: `rehla.outbox.dead_letter_count`
- **Threshold**: `> 0`
- **Condition**: Message reached maximum retry attempts (`attempts >= 5`) and moved to failed state.
- **Runbook**:
  1. Query failed outbox entries:
     ```sql
     SELECT id, event_type, last_error, attempts FROM outbox_messages WHERE status = 'failed';
     ```
  2. Check downstream integration availability (SMS Gateway, Email SMTP).
  3. Correct payload or upstream provider credentials, then re-queue via Admin panel notification resend action.

### P1: Elevated HTTP 5xx Error Rate
- **Metric**: `http_requests_total{status=~"5.."}` / `http_requests_total`
- **Threshold**: `> 2.0%` over rolling 5-minute window.
- **Condition**: Systemic server exceptions on Web or Admin interfaces.
- **Runbook**:
  1. Check application exception logs in `storage/logs/laravel.log`.
  2. Correlate with `X-Trace-Id` headers.
  3. Verify PostgreSQL connection pool saturation and query latency.
  4. Validate `/ready` probe output for degraded backend components.

### P2: Malicious or Corrupt Document Upload
- **Metric**: `rehla.documents.scan_failures_total`
- **Threshold**: `> 0`
- **Condition**: File upload rejected during magic-byte verification or antivirus scanning.
- **Runbook**:
  1. Verify uploaded document has remained quarantined in private storage and was not promoted to `clean`.
  2. Inspect uploader `owner_id` and IP address from `upload_sessions` for pattern analysis.
  3. Account lockout review if automated malicious payload probing is suspected.
