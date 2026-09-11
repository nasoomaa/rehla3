# EPIC-017 — Audit and Compliance

**Summary:** Audit and Compliance
**Stories:** STORY-0076, STORY-0077
**Primary sources:** `specs/domains/audit.md`
**Status:** 0/2 done

## STORY-0076

**Epic:** EPIC-017 — Audit and Compliance
**Title:** Immutable Audit Logging and State Traceability

**As a** system auditor
**I want** all sensitive system operations to automatically append immutable, sanitized audit log entries within the same database transaction
**So that** administrative and state operations maintain strict accountability and historical auditability

**Acceptance criteria:**
- AC-1: AppendAuditEntry appends audit records for all mandatory sensitive operations inside the business database transaction, failing the enclosing transaction if audit insertion fails. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0082`
- AC-2: Database triggers prevent UPDATE and DELETE operations on audit log entries, aborting with SQLSTATE error code. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0082`
- AC-3: Audit snapshots scrub passwords, raw credentials, and session tokens before persisting state snapshots. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0082`
- AC-4: Audit entries inherit X-Correlation-ID header and capture the specific staff member's actor_id, prohibiting generic or anonymous writes. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0082`

**Sources:**
- `specs/domains/audit.md:1-102`

**Status:** pending

## STORY-0077

**Epic:** EPIC-017 — Audit and Compliance
**Title:** Audit Log Access Control and Inspection

**As a** compliance administrator
**I want** to query and filter audit log records when possessing proper abilities and verified MFA session
**So that** I can review historical system operations and compliance trails

**Acceptance criteria:**
- AC-1: QueryAuditLog returns filtered, paginated audit records for staff with audit.view ability and active verified TOTP MFA session. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0084`
- AC-2: Requests to view audit logs from staff lacking audit.view ability or active MFA session are rejected with HTTP 403 audit.access_denied. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0084`

**Sources:**
- `specs/domains/audit.md:1-102`

**Status:** pending