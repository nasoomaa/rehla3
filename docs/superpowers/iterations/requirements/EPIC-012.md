# EPIC-012 — Orders and Purchasing

**Summary:** Orders and Purchasing
**Stories:** STORY-0052, STORY-0053, STORY-0054, STORY-0055, STORY-0056, STORY-0057
**Primary sources:** `specs/domains/orders-and-purchasing.md`, `specs/journeys/journey-05-service-order-and-instant-purchase.md`, `specs/journeys/journey-07-family-multiple-orders.md`, `specs/journeys/journey-08-order-submission-edge-cases.md`, `specs/test-vectors/idempotency-and-deduplication.md`
**Status:** 0/6 done

## STORY-0052

**Epic:** EPIC-012 — Orders and Purchasing
**Title:** Atomic Order Submission and Purchase

**As a** Customer
**I want** to submit commercial orders paid via wallet debit using an Idempotency-Key
**So that** my purchase succeeds atomically with execution links and zero partial state

**Acceptance criteria:**
- AC-1: SubmitOrder executes in a single database transaction adhering to strict lock sequencing protocol (Idempotency -> Wallet -> Traveler -> Documents sorted by ID). · impact:`journey` · seam:`integration` · scenario:`SCENARIO-0048`
- AC-2: SubmitOrder verifies authoritative price matches accepted_price at microsecond of execution, form schema version is current, traveler belongs to customer, and documents are clean. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0048`
- AC-3: Idempotency key enforcement returns cached order response for identical payload, or HTTP 409 order.idempotency_conflict for payload mismatch. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0048`
- AC-4: Successful submission debits wallet, creates permanent CommercialOrder in paid status, triggers CreateExecution in Fulfillment, and enqueues OrderSubmitted to Outbox. · impact:`journey` · seam:`app-level` · scenario:`SCENARIO-0048`

**Sources:**
- `specs/domains/orders-and-purchasing.md:1-148`

**Status:** pending

## STORY-0053

**Epic:** EPIC-012 — Orders and Purchasing
**Title:** Customer Order Details and History

**As a** Customer or Authorized Staff
**I want** to view order history and detailed immutable order records including frozen service and traveler snapshots
**So that** I can audit past purchases and track fulfillment execution

**Acceptance criteria:**
- AC-1: GetOrderDetails returns commercial order data with frozen service snapshot, frozen traveler snapshot, debit reference, and current execution link to authorized customer or staff with orders.view. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0052`
- AC-2: Unauthorized access to order details returns HTTP 404. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0052`
- AC-3: ListCustomerOrders returns customer commercial orders in chronological order descending by creation date. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0052`

**Sources:**
- `specs/domains/orders-and-purchasing.md:1-148`

**Status:** pending

## STORY-0054

**Epic:** EPIC-012 — Orders and Purchasing
**Title:** Service Order and Atomic Purchase Journey

**As a** Authenticated Customer
**I want** to select a saved traveler, complete a dynamic application form, and atomically purchase a service debiting my wallet
**So that** I receive an immutable commercial order record, an operational execution case, and confirmation notification

**Acceptance criteria:**
- AC-1: Checkout sends POST /api/v1/order-submissions with an Idempotency-Key; system locks idempotency record, wallet row, and document records within a single atomic PostgreSQL transaction. · impact:`journey` · seam:`integration` · scenario:`JOURNEY-0005`
- AC-2: Transaction re-verifies authoritative price, active form version, traveler ownership, and document clean status before debiting wallet, creating order, calling CreateExecution, appending audit entry, and enqueuing OrderSubmitted. · impact:`journey` · seam:`integration` · scenario:`JOURNEY-0005`
- AC-3: On success, HTTP 201 Created returns order reference ORD-YYYYMM-XXXX; browser redirects to order details page showing order status 'Order Received (In Queue)' and updated wallet balance. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0005`
- AC-4: Duplicate submission with identical Idempotency-Key and identical payload returns HTTP 200 OK with cached order, zero additional debit. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0005`
- AC-5: Price change detected during transaction: HTTP 409 service.price_changed with old and new prices returned; wallet not debited; UI updates with new price for re-confirmation. · impact:`cross-surface` · seam:`app-level` · scenario:`JOURNEY-0005`
- AC-6: Form version mismatch during transaction: HTTP 409 form.version_outdated with new version ID returned; UI reloads form with new version. · impact:`cross-surface` · seam:`app-level` · scenario:`JOURNEY-0005`
- AC-7: Insufficient balance during transaction: HTTP 422 wallet.insufficient_balance returned; wallet not debited; UI displays 'Top Up Wallet' link. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0005`

**Sources:**
- `specs/journeys/journey-05-service-order-and-instant-purchase.md:1-192`

**Status:** pending

## STORY-0055

**Epic:** EPIC-012 — Orders and Purchasing
**Title:** Family Multiple Independent Orders Journey

**As a** Customer
**I want** to place multiple independent commercial orders for different family members from a single funded wallet
**So that** each family member's order and execution proceed independently without blocking each other

**Acceptance criteria:**
- AC-1: Each order for a distinct traveler creates a separate CommercialOrder record with its own debit reference, frozen traveler snapshot, and linked service execution case. · impact:`local` · seam:`integration` · scenario:`JOURNEY-0007`
- AC-2: Wallet balance decrements sequentially by the exact price per order; customer dashboard displays updated remaining balance after each order. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0007`
- AC-3: Operational fulfillment for each family member's execution proceeds completely independently; an action or delay on one execution does NOT block or alter other executions. · impact:`local` · seam:`integration` · scenario:`JOURNEY-0007`
- AC-4: When wallet balance drops to exactly zero after sequential orders, subsequent order submission returns HTTP 422 wallet.insufficient_balance. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0007`

**Sources:**
- `specs/journeys/journey-07-family-multiple-orders.md:1-142`

**Status:** pending

## STORY-0056

**Epic:** EPIC-012 — Orders and Purchasing
**Title:** Order Submission Concurrency and Edge Case Resilience

**As a** System
**I want** to handle concurrent purchases, mid-flight price changes, form version updates, network retries, and idempotency key conflicts deterministically
**So that** wallet balances never go negative, orders are never created without debits, and debits are never committed without orders

**Acceptance criteria:**
- AC-1: Concurrent purchases from two browser tabs are serialized via wallet SELECT FOR UPDATE; only the first succeeds; the second fails with wallet.insufficient_balance after lock release reveals zero balance. · impact:`local` · seam:`integration` · scenario:`JOURNEY-0008`
- AC-2: Price change between form load and submission: transaction detects mismatch, rolls back before DebitWallet, returns HTTP 409 service.price_changed with old and new prices, and leaves wallet unchanged. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0008`
- AC-3: Form version change between form load and submission: transaction detects version mismatch, rolls back before DebitWallet, returns HTTP 409 form.version_outdated. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0008`
- AC-4: Network retry with same Idempotency-Key and identical payload fingerprint: returns cached HTTP 200 OK with original order, zero additional debit or order creation. · impact:`local` · seam:`integration` · scenario:`JOURNEY-0008`
- AC-5: Idempotency key reuse with different payload fingerprint: HTTP 409 order.idempotency_conflict returned, no debit or order created. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0008`
- AC-6: Under all concurrent or interrupted conditions, wallet balance remains >= 0, ledger credits/debits match actual balance movements, and commercial orders are never created without a successful wallet debit. · impact:`journey` · seam:`integration` · scenario:`JOURNEY-0008`

**Sources:**
- `specs/journeys/journey-08-order-submission-edge-cases.md:1-123`

**Status:** pending

## STORY-0057

**Epic:** EPIC-012 — Orders and Purchasing
**Title:** Order Idempotency Key Scoping and Deduplication

**As a** System Checkout Engine
**I want** to enforce Idempotency-Key scoping, identical-replay deduplication, and payload-mismatch conflict detection
**So that** no duplicate orders or wallet debits occur from network retries

**Acceptance criteria:**
- AC-1: IDP-01: First submission creates order, returns HTTP 201 Created. · impact:`none` · seam:`unit`
- AC-2: IDP-02: Identical replay (same key + same payload) returns HTTP 200 OK with cached order, zero debit. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0096`
- AC-3: IDP-03/IDP-04: Same key with different payload fingerprint returns HTTP 409 order.idempotency_conflict. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0096`
- AC-4: IDP-05: Same key value from different account_id creates independent new order (key is scoped to account). · impact:`local` · seam:`integration` · scenario:`SCENARIO-0096`
- AC-5: IDP-06: Missing Idempotency-Key header returns HTTP 400 Bad Request. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0096`

**Sources:**
- `specs/test-vectors/idempotency-and-deduplication.md:1-52`

**Status:** pending