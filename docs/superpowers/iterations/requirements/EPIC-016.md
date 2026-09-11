# EPIC-016 — Notifications and Outbox

**Summary:** Notifications and Outbox
**Stories:** STORY-0074, STORY-0075
**Primary sources:** `specs/domains/notifications.md`
**Status:** 0/2 done

## STORY-0074

**Epic:** EPIC-016 — Notifications and Outbox
**Title:** Transactional Outbox and Event Notification Pipeline

**As a** system background worker
**I want** outbox notifications to be enqueued atomically within business transactions and processed reliably via worker polling, exponential backoff retries, and dead-letter containment
**So that** customer notifications are guaranteed at-least-once delivery without running external I/O inside database transactions or creating duplicate messages

**Acceptance criteria:**
- AC-1: AppendOutboxMessage inserts records into in_app_notifications (unread) and outbox_messages (available) atomically within the business transaction without executing external network calls. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0076`
- AC-2: ClaimOutboxBatch locks available messages with FOR UPDATE SKIP LOCKED up to 50 records, setting status=locked, locked_by=worker_id, and incrementing attempts. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0076`
- AC-3: HandleDeliveryFailure schedules exponential backoff retries (NOW + 2^attempts * 15s) when attempts < 5. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0076`
- AC-4: HandleDeliveryFailure sets message status to dead_letter, logs an Audit entry, and triggers monitoring alerts when attempts >= 5 without halting worker queues. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0076`
- AC-5: Outbox messages enforce deduplication_key uniqueness to prevent duplicate customer messages across retries. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0076`
- AC-6: Worker crash recovery automatically unlocks messages whose lease expired (locked_at < NOW() - 60 seconds) back to available. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0076`

**Sources:**
- `specs/domains/notifications.md:1-163`

**Status:** pending

## STORY-0075

**Epic:** EPIC-016 — Notifications and Outbox
**Title:** Customer In-App Notifications Management

**As a** authenticated customer
**I want** to view paginated in-app notifications and mark notifications as read
**So that** I can stay updated on my top-ups, orders, and fulfillment requirements

**Acceptance criteria:**
- AC-1: ListCustomerNotifications returns paginated notifications and unread count scoped strictly to the authenticated customer. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0081`
- AC-2: MarkNotificationRead updates status to read and sets read_at timestamp idempotently. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0081`
- AC-3: Requests to view or mutate notifications belonging to another customer return HTTP 404 Not Found. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0081`

**Sources:**
- `specs/domains/notifications.md:1-163`

**Status:** pending