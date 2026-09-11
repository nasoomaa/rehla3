# EPIC-004 — Notifications and Event Delivery

**Summary:** Notifications and Event Delivery
**Stories:** STORY-0021, STORY-0022, STORY-0023, STORY-0024
**Primary sources:** `specs/contracts/outbox-and-notifications-delivery.md`
**Status:** 0/4 done

## STORY-0021

**Epic:** EPIC-004 — Notifications and Event Delivery
**Title:** Transactional Outbox Event Appending

**As a** domain package producer
**I want** to append domain events to the outbox table within the primary database transaction
**So that** events are stored atomically with business state changes and rolled back if the transaction fails

**Acceptance criteria:**
- AC-1: When a producer calls append(), an outbox record must be created in outbox_messages with status available, default payload_version 1, and attempts set to 0. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0018`
- AC-2: Producers must supply a deterministic deduplication_key which enforces idempotency and prevents duplicate message delivery. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0018`
- AC-3: Outbox appending must execute within the primary database transaction without initiating direct HTTP requests or external socket calls, ensuring atomic rollback if the transaction fails. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0018`

**Sources:**
- `specs/contracts/outbox-and-notifications-delivery.md:38-58`

**Status:** pending

## STORY-0022

**Epic:** EPIC-004 — Notifications and Event Delivery
**Title:** Outbox Worker Polling and Lock Acquisition

**As a** background notification worker
**I want** to poll available outbox messages and claim locks using FOR UPDATE SKIP LOCKED
**So that** multiple worker instances process messages concurrently without duplicate handling

**Acceptance criteria:**
- AC-1: Workers must query outbox_messages filtering by status = 'available' and available_at <= NOW(), ordered by id ASC with limit 50 using FOR UPDATE SKIP LOCKED. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0019`
- AC-2: Claiming messages updates their status to locked, sets locked_at to NOW(), assigns locked_by to the worker ID, and increments attempts by 1. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0019`

**Sources:**
- `specs/contracts/outbox-and-notifications-delivery.md:62-85`

**Status:** pending

## STORY-0023

**Epic:** EPIC-004 — Notifications and Event Delivery
**Title:** Outbox Delivery Confirmation, Retry Backoff, and Dead-Lettering

**As a** event distribution pipeline
**I want** to record delivery success, schedule exponential backoff retries on transient failures, and dead-letter exhausted messages
**So that** events are reliably delivered at-least-once and undeliverable events are tracked with audit entries

**Acceptance criteria:**
- AC-1: Upon successful delivery, the outbox message status transitions to delivered, delivered_at is set to NOW(), and locked_by is cleared. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0020`
- AC-2: When delivery fails and attempts are less than 5, status returns to available with available_at rescheduled using exponential backoff: (2 ^ attempts) * 15 seconds. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0020`
- AC-3: When delivery fails on attempt 5 (attempts >= 5), status transitions to dead_letter, monitoring alert is dispatched, and an audit entry is written to audit_entries. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0020`

**Sources:**
- `specs/contracts/outbox-and-notifications-delivery.md:89-127`

**Status:** pending

## STORY-0024

**Epic:** EPIC-004 — Notifications and Event Delivery
**Title:** Outbox Worker Lease Lock Recovery

**As a** system watchdog
**I want** to reset locked outbox messages whose lease threshold has exceeded 60 seconds back to available status
**So that** orphaned locked messages from crashed workers are automatically reclaimed without manual intervention

**Acceptance criteria:**
- AC-1: Watchdog task running every 60 seconds must transition locked messages with locked_at < NOW() - 60 seconds back to available with locked_at and locked_by reset to NULL. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0021`

**Sources:**
- `specs/contracts/outbox-and-notifications-delivery.md:131-144`

**Status:** pending