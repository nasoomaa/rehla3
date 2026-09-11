# Behavior Scenarios

## Journey Scenarios

## JOURNEY-0001 — JOURNEY: Service discovery and WhatsApp inquiry without side effects

**Kind:** journey
**Proof seam:** e2e
**Owning stories:** STORY-0082

**Preconditions:**
- UAE 30-Day Tourist Visa service is active with published form version and authoritative price 25,000.00 SDG

**Steps:**
1. Guest navigates to Rehla homepage (GET /)
   → Public storefront renders with catalog banner and published services ordered by display_order
2. Guest clicks on UAE 30-Day Tourist Visa service card
   → Service details page renders with high-res imagery, authoritative price 25,000.00 SDG, estimated duration, requirements list, Order Now and Inquire via WhatsApp buttons
3. Guest clicks 'Inquire via WhatsApp'
   → Deep-link URL generated: https://wa.me/249912345678?text=...UAE 30-Day Tourist Visa...25,000.00 SDG
   → Link opens in new window with rel='noopener noreferrer'
4. Guest toggles language to Arabic
   → Page re-renders with dir='rtl' layout
   → WhatsApp deep-link text updated to Arabic message

**Final observables:**
- orders table: 0 records created
- service_executions table: 0 records created
- wallets table: 0 balance change
- User is in external WhatsApp conversation with pre-filled service context

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-01-service-discovery-and-inquiry.md:1-114`

## JOURNEY-0002 — JOURNEY: Account registration, wallet provisioning, and dashboard access

**Kind:** journey
**Proof seam:** e2e
**Owning stories:** STORY-0035

**Preconditions:**
- Email 'mohammed.osman@example.com' is not registered in Rehla

**Steps:**
1. User opens GET /register form
   → Registration form renders with Full Name, Email, Password, Preferred Language fields
2. User submits POST /api/v1/auth/register with Full Name: 'Mohammed Osman', Email: 'mohammed.osman@example.com', Password: 'SecretPass2026!', Language: 'en'
   → Email normalized to lowercase
   → Password complexity validated
   → User record created in active status
   → Wallet provisioned with SDG currency and balance_minor=0 and status active
   → Welcome in-app notification inserted
   → CustomerRegistered outbox message enqueued
   → HTTP 201 Created with Bearer token returned
3. Browser redirects to GET /account dashboard
   → Dashboard displays greeting 'Welcome, Mohammed Osman'
   → Wallet balance displays '0.00 SDG'
   → Travelers vault shows empty state message

**Final observables:**
- users: 1 record with status active
- wallets: 1 record with balance_minor=0 and status active
- in_app_notifications: 1 welcome message
- outbox_messages: 1 CustomerRegistered event available

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-02-account-registration-and-profile.md:1-140`

## JOURNEY-0003 — JOURNEY: Traveler profile save with passport normalization and uniqueness enforcement

**Kind:** journey
**Proof seam:** e2e
**Owning stories:** STORY-0038

**Preconditions:**
- Customer is authenticated
- No traveler with passport P01234567 exists in platform

**Steps:**
1. Customer navigates to /account/travelers and clicks '+ Add Traveler'
   → Traveler form opens with Full Name, DOB, Gender, Passport Number, Issue Date, Expiry Date fields
2. Customer inputs passport ' p 012-345 67 ' and submits traveler form
   → System normalizes passport: strips non-alphanumeric, uppercases → 'P01234567'
   → Validates ^[A-Z0-9]{6,12}$ (passes)
   → Checks global uniqueness (no collision)
   → Inserts travelers record with account_id, normalized passport P01234567
   → Success toast 'Traveler profile saved successfully.' shown
3. Customer views traveler vault
   → Traveler card shows Name, Passport P01234567, Expiry date

**Final observables:**
- travelers: 1 new row with normalized passport P01234567 associated with account_id
- Traveler is immediately selectable in future order flows

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-03-traveler-profile-management.md:1-142`

## JOURNEY-0004 — JOURNEY: Top-Up submission through atomic staff approval and wallet credit

**Kind:** journey
**Proof seam:** e2e
**Owning stories:** STORY-0050

**Preconditions:**
- Customer is authenticated with wallet balance 0.00 SDG
- Bank of Khartoum platform bank account is active
- Staff has topups.review ability with active TOTP MFA

**Steps:**
1. Customer opens top-up form, uploads receipt receipt.png to private storage, gets document_id=doc-rec-101 in clean status
   → HTTP 201 Created with document_id, status pending_scan or clean
2. Customer submits POST /api/v1/top-ups with bank_account_id, amount_minor=5000000 (50,000 SDG), reference ' TXN-987654321 ', document_id=doc-rec-101
   → Amount validated >= 500,000 minor units
   → Reference normalized to TXN987654321
   → Global uniqueness check passes
   → Document marked attached
   → top_up_requests record inserted with status under_review
   → UI redirects to /account/wallet showing Top-Up Under Review status
3. Staff logs in with TOTP MFA, navigates to Top-Up Requests, opens request #401 and clicks 'Approve Transfer'
   → Atomic transaction executes: locks top_up row FOR UPDATE, verifies status=under_review, locks wallet row FOR UPDATE, CreditWallet increases balance by 5000000, top_up status set to approved with reviewer_id and decision_at, audit entry appended, TopUpApproved enqueued to Outbox
4. Outbox worker delivers notification
   → Customer receives in-app notification 'Your wallet top-up of 50,000.00 SDG via Bank of Khartoum has been approved.'
   → Customer wallet balance updates to 50,000.00 SDG

**Final observables:**
- top_up_requests: status approved, reviewer_id set, decision_at set
- wallets: current_balance_minor=5000000
- wallet_ledger_entries: 1 credit entry
- audit_entries: 1 immutable log entry
- in_app_notifications: 1 unread approval notification

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-04-wallet-topup-submission-and-review.md:1-197`

## JOURNEY-0005 — JOURNEY: Atomic service order purchase with wallet debit and execution creation

**Kind:** journey
**Proof seam:** e2e
**Owning stories:** STORY-0054

**Preconditions:**
- Customer authenticated with wallet balance 50,000.00 SDG (5,000,000 minor units)
- UAE 30-Day Tourist Visa active at price 25,000.00 SDG (2,500,000 minor units) with FormVersion #4
- Traveler Ahmed Mohammed Osman (Passport P01234567) in customer vault

**Steps:**
1. Customer clicks 'Order Now', selects traveler Ahmed, fills form (Mother's Name: Fatima Hassan, uploads photo.jpg getting document_id=doc-photo-99 in clean status), reviews checkout summary
   → Checkout summary shows UAE 30-Day Tourist Visa, traveler Ahmed (P01234567), price 25,000.00 SDG, wallet 50,000.00 SDG, remaining after purchase 25,000.00 SDG
2. Customer clicks 'Submit Order' with Idempotency-Key: uuid-order-8811; sends POST /api/v1/order-submissions
   → PostgreSQL transaction begins
   → Locks idempotency record for (account_id, uuid-order-8811)
   → Locks wallet row FOR UPDATE
   → Re-verifies service price = 2,500,000, form version #4 active, traveler #12 owned by customer, doc-photo-99 clean
   → DebitWallet: decrements balance to 2,500,000, inserts ledger entry (debit, 2500000)
   → Inserts orders record: status=paid, service_snapshot, traveler_snapshot, price_paid_minor=2500000
   → CreateExecution: inserts service_executions with status=received and initial changelog
   → AppendAuditEntry: purchase record
   → Enqueues OrderSubmitted to outbox
   → HTTP 201 Created with order reference ORD-202609-1001 returned
3. Browser redirects to /account/orders/ORD-202609-1001
   → Success screen shows Order Reference ORD-202609-1001, Service UAE 30-Day Tourist Visa, Beneficiary Ahmed Mohammed Osman, Payment Paid (25,000.00 SDG), Execution Status Order Received (In Queue)
   → Header wallet balance updates to 25,000.00 SDG

**Final observables:**
- wallets: balance_minor=2500000
- wallet_ledger_entries: 1 debit entry
- orders: 1 record status=paid with frozen snapshots
- service_executions: 1 record status=received
- outbox_messages: 1 OrderSubmitted event

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-05-service-order-and-instant-purchase.md:1-192`

## JOURNEY-0006 — JOURNEY: Execution fulfillment with customer action request and visa download

**Kind:** journey
**Proof seam:** e2e
**Owning stories:** STORY-0064

**Preconditions:**
- Commercial Order ORD-202609-1001 paid and execution exec-9988 in received status
- Fulfillment Officer has executions.transition ability

**Steps:**
1. Staff transitions exec-9988: received → under_review → processing
   → Each transition appended to execution_status_history
   → Audit log entries written
   → Customer portal immediately reflects status updates
2. Staff submits RequestCustomerAction with EN/AR instructions about dark photo background
   → CustomerActionRequest created as open
   → Execution status transitions to action_required
   → CustomerActionRequested event enqueued
   → Customer dashboard shows 'Action Required From You' / 'مطلوب إجراء منك' banner with exact instructions
3. Customer uploads replacement photo photo_white_bg.jpg, gets document_id=doc-photo-clear-102 in clean status, submits response
   → CustomerActionRequest marked resolved
   → Execution transitions to action_received
   → Document marked attached
   → CustomerActionReceived event enqueued
   → Customer UI shows 'Requested Action Received.'
4. Staff inspects new photo, transitions to processing, uploads visa PDF, calls CompleteExecution with doc-visa-final-99
   → Execution status transitions to completed
   → issued_document_id=doc-visa-final-99 set
   → completed_at set
   → ExecutionCompleted notification enqueued
5. Customer opens order details and clicks 'Download Issued Visa (PDF)'
   → System delivers authorized stream with Content-Disposition: attachment; filename='UAE_Visa_Ahmed_Osman.pdf'

**Final observables:**
- service_executions: status=completed, issued_document_id set, completed_at set
- execution_status_history: complete changelog received→under_review→processing→action_required→action_received→processing→completed
- audit_entries: complete trail with staff IDs
- Customer holds issued visa PDF

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-06-execution-tracking-and-customer-action.md:1-168`

## JOURNEY-0007 — JOURNEY: Family multi-order sequential purchases with independent execution lifecycles

**Kind:** journey
**Proof seam:** e2e
**Owning stories:** STORY-0055

**Preconditions:**
- Customer has 3 traveler profiles: Ahmed (P01234567), Sarah (P02345678), Mohammed (P03456789)
- Customer wallet balance = 75,000.00 SDG (7,500,000 minor units)
- UAE Tourist Visa active at 25,000.00 SDG

**Steps:**
1. Customer submits Order 1 for Ahmed (25,000 SDG)
   → Wallet debited: balance = 50,000.00 SDG
   → ORD-202609-1001 created in paid status
   → exec-1001 created in received status
2. Customer submits Order 2 for Sarah (25,000 SDG)
   → Wallet debited: balance = 25,000.00 SDG
   → ORD-202609-1002 created in paid status
   → exec-1002 created in received status
3. Customer submits Order 3 for Mohammed (25,000 SDG)
   → Wallet debited: balance = 0.00 SDG
   → ORD-202609-1003 created in paid status
   → exec-1003 created in received status
4. Customer navigates to My Orders and dashboard
   → 3 independent order cards displayed
   → Wallet Balance shows 0.00 SDG
5. Customer attempts 4th order when wallet = 0
   → HTTP 422 wallet.insufficient_balance returned

**Final observables:**
- orders: 3 distinct records, each with own debit reference and traveler snapshot
- service_executions: 3 distinct cases
- wallets: balance_minor=0
- wallet_ledger_entries: 3 distinct debit entries

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-07-family-multiple-orders.md:1-142`

## JOURNEY-0008 — JOURNEY: Concurrent order submissions with balance for only one - pessimistic locking

**Kind:** journey
**Proof seam:** e2e
**Owning stories:** STORY-0056

**Preconditions:**
- Customer wallet balance = 25,000.00 SDG (2,500,000 minor units)
- Customer opens Tab 1 for UAE Visa (25,000 SDG) and Tab 2 for Qatar Visa (25,000 SDG)

**Steps:**
1. Customer clicks Submit Order simultaneously in both browser tabs
   → Both requests arrive concurrently at application server
   → Request 1 acquires wallet SELECT FOR UPDATE lock
   → Request 2 blocks waiting for lock
   → Request 1 evaluates balance: 2,500,000 >= 2,500,000 (Sufficient)
   → Request 1 debits, creates ORD-1001, commits, releases lock
   → Request 2 unblocks, reads updated balance: 0 minor units
   → Request 2 evaluates: 0 < 2,500,000 (Insufficient)
   → Request 2 rolls back immediately
2. Tab 1 and Tab 2 receive responses
   → Tab 1: Order Confirmed! Reference: ORD-1001
   → Tab 2: Your wallet balance is insufficient...

**Final observables:**
- Wallet balance = 0 (non-negative, never went negative)
- Exactly 1 order created
- Exactly 1 ledger debit entry

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-08-order-submission-edge-cases.md:1-123`

## JOURNEY-0009 — JOURNEY: Network retry with idempotency key - exact-once debit enforcement

**Kind:** journey
**Proof seam:** e2e
**Owning stories:** STORY-0056

**Preconditions:**
- Customer submits order with Idempotency-Key: 'idemp-key-555'; server processes and debits wallet, creates ORD-1005; network drops before HTTP 201 response received

**Steps:**
1. Mobile client reconnects after 10 seconds and retries POST with Idempotency-Key: 'idemp-key-555' and identical payload
   → Server locks idempotency_keys for (account_10, idemp-key-555)
   → Finds existing record with status completed
   → SHA-256 fingerprint of payload matches stored fingerprint
   → Transaction exits without DebitWallet or CreateOrder
   → Returns HTTP 200 OK with cached ORD-1005 details

**Final observables:**
- Wallet debited exactly once
- Exactly 1 order ORD-1005 exists

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-08-order-submission-edge-cases.md:1-123`

## Surface Scenarios

## SCENARIO-0001 — Admin executes top-up approval with MFA verification and audit trail

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0002, STORY-0007

**Preconditions:**
- Staff member possesses 'topups.view' and 'topups.review' abilities
- Staff member has verified MFA session within last 4 hours
- Top-Up request is in 'pending' status with amount_minor 5,000,000

**Action:**
- Staff submits ApproveTopUp command via TopUps\Actions\ApproveTopUp DTO

**Expected observables:**
- Row lock is acquired on the top-up request
- Wallet domain CreditWallet action is called with reference_type 'top_up_approval'
- Top-Up status transitions to 'approved'
- Audit log entry is appended to audit_entries
- TopUpApproved notification is sent to customer
- Top-Up status is 'approved'
- Customer wallet balance is increased by 5,000,000 minor units
- New record present in audit_entries referencing reviewer ID

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/admin-operations-contract.md:48-62`
- `specs/contracts/bank-transfer-receipt-contract.md:59-77`

## SCENARIO-0002 — Reviewer collision handling on already approved top-up

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0002

**Preconditions:**
- Top-Up request #101 was already approved by Reviewer A
- Reviewer B has the detail view open for Top-Up request #101

**Action:**
- Reviewer B attempts to approve Top-Up request #101

**Expected observables:**
- TopUpAlreadyDecidedException is raised
- System refreshes the resource view
- UI presents alert: 'This request has already been approved by Reviewer A'
- Top-Up state remains approved by Reviewer A without double crediting
- Alert is visible on Reviewer B UI screen

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/admin-operations-contract.md:99-103`

## SCENARIO-0003 — Admin rejects top-up request with mandatory reason code

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0002, STORY-0007

**Preconditions:**
- Staff member possesses 'topups.review' ability
- Top-Up request #202 is in 'pending' status

**Action:**
- Staff submits RejectTopUp command with rejection_reason of at least 10 characters

**Expected observables:**
- Top-Up status transitions to 'rejected'
- Audit log entry is created with rejection rationale
- TopUpRejected notification is dispatched to customer
- Top-Up status is 'rejected'
- Customer wallet balance is unchanged
- Notification containing rejection reason is recorded for customer

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/admin-operations-contract.md:61-71`
- `specs/contracts/bank-transfer-receipt-contract.md:71-87`

## SCENARIO-0004 — Invalid service execution transition rejection

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0003

**Preconditions:**
- Service execution case is in 'completed' state

**Action:**
- Staff attempts to transition state from 'completed' to 'processing'

**Expected observables:**
- InvalidExecutionTransitionException is thrown
- Modal error is displayed: 'Cannot transition execution from completed to processing.'
- Service execution state remains 'completed'

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/admin-operations-contract.md:71-78`

## SCENARIO-0005 — Secure temporary URL generation for sensitive document viewing

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0004

**Preconditions:**
- Staff member is logged in with active session
- Private document 'doc-uuid-101' exists in storage

**Action:**
- Staff clicks to inspect private receipt document

**Expected observables:**
- Admin panel requests signed URL via Documents\Contracts\GetSecureDownloadUrl
- Returned URL expires within 15 minutes
- Response headers specify Content-Disposition: inline and X-Content-Type-Options: nosniff
- Direct public S3/CDN link is never exposed in DOM or API response

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/admin-operations-contract.md:80-92`

## SCENARIO-0006 — Passport PII masking and authorized unmasking

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0004

**Preconditions:**
- Traveler record exists with passport number 'P01234567'

**Action:**
- Staff member views customer traveler list
- Staff member with 'travelers.view_sensitive' ability clicks to unmask passport number

**Expected observables:**
- Passport number displays as 'P*****567'
- Passport number unmasks to display 'P01234567'
- Unmasking fails or hides action button if staff member lacks 'travelers.view_sensitive'

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/admin-operations-contract.md:93-97`

## SCENARIO-0007 — Normalize transaction reference and reject duplicate submissions

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0006

**Preconditions:**
- Active platform bank account ID 2 exists
- Previous top-up exists for bank_account_id 2 with transaction reference normalized to 'BOK987654321'

**Action:**
- Customer submits top-up with bank_account_id 2, transaction_reference 'BOK-987654321', amount_minor 5000000

**Expected observables:**
- System normalizes transaction reference to 'BOK987654321'
- Database constraint uq_bank_account_reference flags duplicate key collision
- API returns HTTP 422 with code 'top_up.reference_used'
- Duplicate top-up record is not created
- Error response contains message about already-used reference

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/bank-transfer-receipt-contract.md:35-46`

## SCENARIO-0008 — Registration with existing email returns validation error

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0009

**Preconditions:**
- A customer account with email 'ahmed@example.com' exists in the system

**Action:**
- An unauthenticated user sends POST /api/v1/auth/register with email 'ahmed@example.com'

**Expected observables:**
- The server returns HTTP 422 Unprocessable Entity with error code 'auth.email_exists'
- The response body follows RFC 7807 problem+json format with error details
- No new account is created; error code auth.email_exists is present in response

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:68-70`

## SCENARIO-0009 — Successful customer registration returns token

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0009

**Preconditions:**
- No account exists for email 'ahmed@example.com'

**Action:**
- An unauthenticated user posts valid registration details to POST /api/v1/auth/register

**Expected observables:**
- The server returns HTTP 201 Created containing user object and Bearer token
- Token can be used to authenticate subsequent requests

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:45-69`

## SCENARIO-0010 — Suspended account login returns 403

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0010

**Preconditions:**
- A registered customer account has been suspended

**Action:**
- User attempts POST /api/v1/auth/login with valid credentials

**Expected observables:**
- The server returns HTTP 403 Forbidden with code 'auth.account_suspended'
- No Bearer token is issued; account remains suspended

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:72-76`

## SCENARIO-0011 — Logout revokes Bearer token

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0010

**Preconditions:**
- An authenticated customer with active Bearer token

**Action:**
- The customer calls POST /api/v1/auth/logout with their token

**Expected observables:**
- The server returns HTTP 204 No Content and invalidates the token
- Subsequent requests with the same token return 401 Unauthorized

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:78-80`

## SCENARIO-0012 — Traveler creation normalizes passport and rejects duplicate

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0012

**Preconditions:**
- An existing traveler profile has passport number 'P01234567'

**Action:**
- A customer submits POST /api/v1/travelers with passport number ' p01234567 '

**Expected observables:**
- The server normalizes the passport to 'P01234567'
- The server returns HTTP 422 Unprocessable Entity with code 'traveler.passport_conflict'
- No duplicate traveler record is created; conflict error is returned

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:163-167`

## SCENARIO-0013 — Atomic order checkout debits balance and creates execution

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0016

**Preconditions:**
- A customer with 50,000.00 SDG wallet balance purchasing a 25,000.00 SDG service

**Action:**
- The customer posts to POST /api/v1/order-submissions with valid details and unique Idempotency-Key

**Expected observables:**
- The customer wallet is debited by 25,000.00 SDG
- An order record and an execution record in state 'received' are created
- The server returns HTTP 201 Created with order reference and execution ID
- Wallet balance reduced by 2,500,000 minor units; execution_status is 'received'

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:250-279`

## SCENARIO-0014 — Order checkout with insufficient balance rejected

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0016

**Preconditions:**
- A customer with 10,000.00 SDG wallet balance attempting to purchase a 25,000.00 SDG service

**Action:**
- The customer posts to POST /api/v1/order-submissions

**Expected observables:**
- The server aborts transaction and returns HTTP 422 with code 'wallet.insufficient_balance'
- No debit occurs on customer wallet balance
- No order or execution record created; wallet balance unchanged

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:282-284`

## SCENARIO-0015 — Replaying order submission with identical Idempotency-Key returns original order

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0017

**Preconditions:**
- A previously completed order submission with Idempotency-Key 'key-uuid-101'

**Action:**
- The client resends identical POST /api/v1/order-submissions request with Idempotency-Key 'key-uuid-101'

**Expected observables:**
- The server returns HTTP 200 OK with the original order response payload
- Zero additional debits are made to the customer wallet
- Wallet debited exactly once; original order data returned on replay

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:309-312`

## SCENARIO-0016 — Reusing Idempotency-Key with different payload returns conflict error

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0017

**Preconditions:**
- A previously completed order submission with Idempotency-Key 'key-uuid-101' for traveler ID 12

**Action:**
- The client sends POST /api/v1/order-submissions with Idempotency-Key 'key-uuid-101' but traveler ID 99

**Expected observables:**
- The server returns HTTP 409 Conflict with code 'order.idempotency_conflict'
- No new order created; conflict error returned

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:311-312`

## SCENARIO-0017 — Customer action response updates execution state

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0018

**Preconditions:**
- An execution in state 'action_required' for customer's active order

**Action:**
- The customer posts to POST /api/v1/executions/{id}/actions/{id}/responses with clean document IDs and notes

**Expected observables:**
- The execution state transitions from 'action_required' to 'action_received'
- Execution status is 'action_received'

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:290-293`

## SCENARIO-0018 — Outbox event appended atomically during transaction

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0021

**Preconditions:**
- A domain package is inside an open database transaction
- OutboxContract.append() is called with a deterministic deduplication key

**Action:**
- Producer calls append() with eventName='order.submitted' and deduplication_key='order_submitted:123'

**Expected observables:**
- A new outbox_messages record is inserted with status='available', attempts=0, payload_version=1
- Record is created within the same transaction and rolled back atomically if transaction fails
- Record visible in outbox_messages with correct deduplication_key after transaction commits
- Record absent from outbox_messages if transaction rolls back

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/outbox-and-notifications-delivery.md:38-58`

## SCENARIO-0019 — Worker polls and claims available outbox records

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0022

**Preconditions:**
- 3 outbox messages with status='available' exist
- A worker instance is running

**Action:**
- Worker runs polling query with FOR UPDATE SKIP LOCKED

**Expected observables:**
- Worker acquires locks on available messages
- Messages' status is updated to 'locked', locked_at set, attempts incremented by 1
- Messages locked to the claiming worker's locked_by identifier
- No other concurrent worker can claim the same messages

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/outbox-and-notifications-delivery.md:62-85`

## SCENARIO-0020 — Exhausted outbox retries transition to dead-letter and audit log

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0023

**Preconditions:**
- An outbox message has attempts=4 and delivery fails again

**Action:**
- Worker attempts delivery and receives failure on attempt 5

**Expected observables:**
- Message status transitions to 'dead_letter'
- An audit entry is written to audit_entries
- A monitoring alert is dispatched
- Message status is 'dead_letter' with last_error populated
- Audit entry exists recording the dead-letter event

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/outbox-and-notifications-delivery.md:117-127`

## SCENARIO-0021 — Watchdog reclaims expired outbox worker locks

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0024

**Preconditions:**
- An outbox message has status='locked' with locked_at more than 60 seconds ago (worker crashed)

**Action:**
- Watchdog runs its scheduled query

**Expected observables:**
- Orphaned message's status transitions to 'available'
- locked_at and locked_by are reset to NULL
- Message is available for re-processing by another worker

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/outbox-and-notifications-delivery.md:131-144`

## SCENARIO-0022 — Document upload validation magic bytes check and malware quarantine

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0026

**Preconditions:**
- A file with .png extension but PDF magic bytes is prepared
- A legitimate JPEG file with embedded malware signature is prepared

**Action:**
- Upload PNG-extension file with PDF magic bytes
- Upload JPEG file matching ClamAV malware signature

**Expected observables:**
- Server returns HTTP 422 document.verification_failed
- File is quarantined; status set to 'rejected'; server returns HTTP 422
- Neither file is stored in clean state
- Malware file is in quarantine with rejected status

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/storage-and-document-pipeline.md:44-64`

## SCENARIO-0023 — Authorized streaming security headers and permission enforcement

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0028

**Preconditions:**
- A private document 'doc-123' owned by customer A exists
- Customer B exists with no ownership of this document
- A staff member without documents.view_sensitive exists

**Action:**
- Authorized customer A requests GET /api/v1/documents/doc-123/content
- Customer B requests GET /api/v1/documents/doc-123/content
- Staff without documents.view_sensitive requests document

**Expected observables:**
- Response includes X-Content-Type-Options: nosniff and Cache-Control: private, no-cache headers
- File content is streamed successfully
- Response is HTTP 404 Not Found
- Response is HTTP 403 Forbidden
- Document content only streamed to authorized parties
- All security headers present on successful response

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/storage-and-document-pipeline.md:80-106`

## SCENARIO-0024 — WhatsApp inquiry button produces no database mutations

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0029, STORY-0030

**Preconditions:**
- Service 'UAE 30-Day Tourist Visa' is active at price 25,000 SDG
- Customer is unauthenticated

**Action:**
- Unauthenticated user clicks 'Inquire via WhatsApp' on service detail page

**Expected observables:**
- A WhatsApp deep-link URL is generated with service name and formatted price
- No order, execution, or draft records are created
- No wallet balance changes occur
- Zero database records created
- Generated URL follows wa.me format with E.164 phone and URL-encoded message

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/contracts/whatsapp-inquiry-contract.md:16-70`

## SCENARIO-0025 — Customer Registration Flow and Event Trigger

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0031

**Preconditions:**
- No active account exists for email user@example.com

**Action:**
- Submit POST registration request with full name 'Jane Doe', email 'User@Example.com', password 'SecurePass1', preferred language 'ar'

**Expected observables:**
- HTTP 201 Created returned with user profile and Bearer token
- Account email is stored as lowercased 'user@example.com'
- Account status is set to active
- CustomerRegistered domain event is emitted
- Wallet initialization is triggered
- Account is active in database with normalized email user@example.com
- Initial Bearer token provides access to customer endpoints

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/identity-and-access.md:1-140`

## SCENARIO-0026 — Duplicate Email Registration Rejection

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0031

**Preconditions:**
- An active customer account already exists with email 'ahmed@example.com'

**Action:**
- Submit POST registration request with email 'Ahmed@Example.com' and valid registration parameters

**Expected observables:**
- HTTP 422 Unprocessable Entity returned with error code auth.email_exists
- No duplicate account is created
- Original account remains unchanged

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/identity-and-access.md:1-140`

## SCENARIO-0027 — Customer Authentication and Session Suspension Invalidation

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0032, STORY-0034

**Preconditions:**
- Active customer account exists with email 'customer@example.com'

**Action:**
- Submit authentication with email 'CUSTOMER@EXAMPLE.COM'
- Admin suspends customer account
- Customer submits API request using previously issued Bearer token

**Expected observables:**
- HTTP 200 OK returned with Bearer token and profile object
- Account status transitions to suspended
- HTTP 401 Unauthorized returned
- Suspended account sessions are rejected on subsequent requests

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/identity-and-access.md:1-140`

## SCENARIO-0028 — Staff Authentication Lockout and TOTP MFA Enforcement

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0033

**Preconditions:**
- Active staff profile exists with topups.review ability and TOTP MFA configured

**Action:**
- Staff user submits login attempt without TOTP token
- Staff user submits login with valid credentials and valid TOTP token

**Expected observables:**
- Authentication rejected with HTTP 401 or HTTP 403 auth.staff_access_denied
- Successful authentication redirecting to administrative control plane overview
- Staff member is granted access with active MFA session

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/identity-and-access.md:1-140`

## SCENARIO-0029 — Add Traveler with Passport Normalization

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0036

**Preconditions:**
- Customer is authenticated

**Action:**
- Submit AddTraveler with passport ' N-123/456_a '

**Expected observables:**
- Passport number is normalized to 'N123456A'
- Traveler profile is saved under customer account_id
- Traveler appears in saved travelers list
- Saved traveler record contains normalized passport N123456A

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/travelers.md:1-130`

## SCENARIO-0030 — Duplicate Passport Registration Rejection with Privacy Protection

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0036

**Preconditions:**
- Passport 'A9876543' is already registered in the platform by Customer A

**Action:**
- Customer B attempts to add traveler with passport 'a987-6543'

**Expected observables:**
- HTTP 422 with error code traveler.passport_conflict without disclosing Customer A details
- Traveler addition is rejected
- No information about existing account ownership is leaked

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/travelers.md:1-130`

## SCENARIO-0031 — Traveler Profile Update Historical Decoupling

**Kind:** surface
**Proof seam:** integration
**Owning stories:** STORY-0037

**Preconditions:**
- Traveler profile exists and has been snapshot in a historical completed order

**Action:**
- Customer updates traveler full name and passport expiry date
- Query past order traveler snapshot

**Expected observables:**
- Saved traveler profile reflects new information for future orders
- Past order displays original unedited traveler snapshot details
- Historical order snapshot remains unchanged
- New profile details apply strictly to future order checkouts

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/travelers.md:1-130`

## SCENARIO-0032 — Catalog Service Creation and Publishing Flow

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0041

**Preconditions:**
- Admin user possesses services.manage ability
- Active published form schema exists for the service

**Action:**
- Create service with name, slug, price
- Publish service with linked published form schema

**Expected observables:**
- Service created in Draft state
- Service transitions to Active state
- Service appears immediately on public catalog storefront
- Active service listed on public catalog with authoritative price

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/service-catalog.md:1-115`

## SCENARIO-0033 — Service Publishing Failure Without Active Form Schema

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0041

**Preconditions:**
- Service exists in Draft status with no linked active form version

**Action:**
- Admin attempts to publish service

**Expected observables:**
- Publishing attempt rejected with error code service.missing_form_version
- Service remains in Draft state
- Service remains invisible on public catalog

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/service-catalog.md:1-115`

## SCENARIO-0034 — Service Price Revision and Price Log Immutability

**Kind:** surface
**Proof seam:** integration
**Owning stories:** STORY-0042

**Preconditions:**
- Active service exists with current price 200 SDG

**Action:**
- Admin updates service price to 300 SDG

**Expected observables:**
- Existing active price history row is closed
- New active price history row created
- ServicePriceUpdated domain event emitted
- Public storefront displays updated price 300 SDG
- Historical price entries are preserved unchanged and immutable
- New checkout quotes retrieve authoritative price 300 SDG

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/service-catalog.md:1-115`

## SCENARIO-0035 — Service Deactivation and New Order Rejection

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0042

**Preconditions:**
- Active service with in-flight orders processing

**Action:**
- Admin deactivates service
- Customer attempts to submit new order for deactivated service

**Expected observables:**
- Service state transitions to Inactive
- Service hidden from public catalog or marked Currently Unavailable
- Order submission fails with HTTP 422 service.unavailable
- New order creation blocked
- In-flight and historical orders continue processing without disruption

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/service-catalog.md:1-115`

## SCENARIO-0036 — Price Conflict Resolution During Order Submission

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0042

**Preconditions:**
- Customer opens service application form with active price quote of 200 SDG
- Admin updates authoritative price to 250 SDG while customer is completing form

**Action:**
- Customer submits completed application form expecting price 200 SDG

**Expected observables:**
- Submission halted, HTTP 409 Conflict with service.price_changed
- Response includes old price 200 SDG and new price 250 SDG details
- Order checkout halted until customer confirms new authoritative price

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/service-catalog.md:1-115`

## SCENARIO-0037 — Initialize new wallet on customer registration

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0043

**Preconditions:**
- Customer account created
- No wallet exists for customer

**Action:**
- Trigger wallet initialization for new customer

**Expected observables:**
- Wallet created with balance=0, status=active, currency=SDG
- Customer UI displays initial balance of 0.00 SDG
- Wallet record exists with status active and 0 balance
- Dashboard displays 0.00 SDG

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/wallet-and-ledger.md:1-151`

## SCENARIO-0038 — Credit active wallet and increment balance

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0044

**Preconditions:**
- Customer wallet is active with balance 10,000.00 SDG
- Top-Up approval event occurs for 5,000.00 SDG with unique reference

**Action:**
- Execute CreditWallet command within DB transaction

**Expected observables:**
- Wallet balance incremented to 15,000.00 SDG
- WalletLedgerEntry created with entry_type=credit
- Notification emitted to user
- UI balance updates to 15,000.00 SDG
- Wallet balance is 15,000.00 SDG
- Ledger entry inserted with new running balance
- User receives credit notification

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/wallet-and-ledger.md:1-151`

## SCENARIO-0039 — Debit wallet with insufficient balance failure

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0044

**Preconditions:**
- Customer wallet balance is 2,000.00 SDG
- Order request requires 5,000.00 SDG

**Action:**
- Execute DebitWallet command

**Expected observables:**
- Pessimistic row lock acquired
- Balance sufficiency check fails (2000 < 5000)
- Transaction rolls back with wallet.insufficient_balance error code
- HTTP status 422 returned
- Wallet balance remains 2,000.00 SDG
- No ledger entry inserted
- Order submission fails

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/wallet-and-ledger.md:1-151`

## SCENARIO-0040 — Attempt debit on frozen wallet

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0043

**Preconditions:**
- Customer wallet status is set to frozen

**Action:**
- Attempt DebitWallet or CreditWallet command

**Expected observables:**
- Operation rejected with error wallet.frozen
- HTTP 403 status returned
- Wallet balance unchanged
- Action blocked

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/wallet-and-ledger.md:1-151`

## SCENARIO-0041 — Run reconciliation discrepancy detection and auto-freeze

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0045

**Preconditions:**
- Wallet current_balance_minor differs from sum of ledger credits minus debits

**Action:**
- Run scheduled RunReconciliation process

**Expected observables:**
- Reconciliation computes sum mismatch
- Critical alert triggered
- Wallet status set to frozen
- Discrepancy logged to Audit
- Wallet status is frozen
- Audit log contains reconciliation discrepancy entry

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/wallet-and-ledger.md:1-151`

## SCENARIO-0042 — Submit top-up request with below minimum amount

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0047

**Preconditions:**
- Customer authenticated
- Active bank account and clean receipt document available

**Action:**
- Submit top-up request with amount 400,000 minor units (4,000 SDG)

**Expected observables:**
- Validation fails for minimum threshold 500,000 minor units
- HTTP 422 top_up.below_minimum returned
- No top-up request created
- User sees validation error message

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/top-ups.md:1-155`

## SCENARIO-0043 — Submit top-up request with reference normalization and duplicate detection

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0047

**Preconditions:**
- Customer authenticated
- Receipt document verified as clean
- Top-up with reference TX-12345 already exists for bank account

**Action:**
- Submit top-up request with reference ' tx - 12345 '

**Expected observables:**
- Reference normalized to TX12345
- DB unique check on (bank_account_id, normalized_reference) detects duplicate
- HTTP 422 top_up.reference_used returned
- Second request rejected
- Original top-up remains unchanged

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/top-ups.md:1-155`

## SCENARIO-0044 — Approve top-up request with TOTP MFA and credit wallet

**Kind:** contract
**Proof seam:** app-level
**Owning stories:** STORY-0048

**Preconditions:**
- Top-up request in under_review status
- Staff user has topups.review ability and active TOTP MFA

**Action:**
- Staff submits ApproveTopUpRequest with valid TOTP code

**Expected observables:**
- Top-up row locked FOR UPDATE
- Wallet CreditWallet invoked in same transaction
- Top-up status updated to approved with reviewer_id and decision_at
- Audit log appended
- TopUpApproved event enqueued to Outbox
- Customer wallet credited and notification sent
- Top-up status is approved
- Customer wallet balance increased
- Notification queued in Outbox

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/top-ups.md:1-155`

## SCENARIO-0045 — Concurrent double approval of top-up request

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0048

**Preconditions:**
- Top-up request in under_review status
- Two reviewers approve simultaneously

**Action:**
- First reviewer completes approval
- Second reviewer attempt finishes lock wait

**Expected observables:**
- Top-up status becomes approved and wallet credited
- Row lock reveals status is already approved
- Returns existing success idempotently without double-crediting wallet
- Top-up approved exactly once
- Wallet credited exactly once

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/top-ups.md:1-155`

## SCENARIO-0046 — Reject top-up request with reason

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0048

**Preconditions:**
- Top-up request in under_review status
- Staff has topups.review ability

**Action:**
- Staff submits RejectTopUpRequest with reason 'Receipt blurred and unreadable'

**Expected observables:**
- Top-up status updated to rejected with reviewer_id, decision_at, and reason
- Audit log entry created
- TopUpRejected enqueued to Outbox
- Wallet balance remains unchanged
- Top-up status is rejected
- Rejection reason visible on customer dashboard
- Zero wallet balance change

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/top-ups.md:1-155`

## SCENARIO-0047 — Deactivate bank account and preserve history

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0049

**Preconditions:**
- Bank account active with existing historical top-up requests
- Staff has banks.manage ability

**Action:**
- Deactivate bank account

**Expected observables:**
- Bank account hidden from new top-up submission forms
- Historical top-up records referencing bank account remain accessible
- New top-ups cannot select deactivated bank account
- Past top-up history displays deactivated bank account details correctly

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/top-ups.md:1-155`

## SCENARIO-0048 — Successful atomic order submission and wallet payment

**Kind:** contract
**Proof seam:** app-level
**Owning stories:** STORY-0052

**Preconditions:**
- Authenticated customer with sufficient wallet balance
- Active service, valid traveler, clean documents, current form version
- Unique Idempotency-Key provided

**Action:**
- Submit order request with Idempotency-Key

**Expected observables:**
- Transaction begins, locks idempotency record and wallet row FOR UPDATE
- Validates service price match, form schema, traveler ownership, document clean status
- Debits wallet for accepted_price and receives ledger_entry_id
- Inserts immutable CommercialOrder in paid status
- Calls CreateExecution in Fulfillment
- Enqueues OrderSubmitted to Outbox
- Returns HTTP 201 Created with order reference ORD-YYYYMM-XXXX
- CommercialOrder record created with status paid
- Wallet balance debited
- Fulfillment execution created
- Order visible in My Orders

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/orders-and-purchasing.md:1-148`

## SCENARIO-0049 — Resubmit order with identical Idempotency-Key

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0052

**Preconditions:**
- Order previously submitted successfully with Idempotency-Key header 'IK-998877'

**Action:**
- Resubmit order with identical Idempotency-Key 'IK-998877' and identical payload

**Expected observables:**
- Idempotency record lock detects matching payload fingerprint
- Cached order response returned (HTTP 200/201)
- No second debit or execution created
- Original order response returned
- Wallet debited only once

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/orders-and-purchasing.md:1-148`

## SCENARIO-0050 — Resubmit order with same Idempotency-Key but modified payload

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0052

**Preconditions:**
- Order previously submitted with Idempotency-Key 'IK-998877'

**Action:**
- Submit order with same Idempotency-Key 'IK-998877' but different service or traveler payload

**Expected observables:**
- Idempotency record lock detects payload fingerprint mismatch
- Transaction rolls back
- HTTP 409 order.idempotency_conflict returned
- Conflict error returned
- No new order created
- Wallet balance unchanged

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/orders-and-purchasing.md:1-148`

## SCENARIO-0051 — Order submission fails due to price change mid-form

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0052

**Preconditions:**
- Service price updated in catalog while customer filling form
- Customer submits accepted_price matching old price

**Action:**
- Submit order with outdated accepted_price

**Expected observables:**
- Authoritative price check fails
- Transaction rolls back
- HTTP 409 service.price_changed returned
- Order submission fails
- Wallet not debited
- No commercial order created

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/orders-and-purchasing.md:1-148`

## SCENARIO-0052 — Unauthorized access to commercial order details

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0053

**Preconditions:**
- Order belongs to Customer A
- Customer B is authenticated

**Action:**
- Customer B requests GetOrderDetails for Customer A's order

**Expected observables:**
- Authorization check fails
- HTTP 404 returned (masking existence of order)
- Access denied with HTTP 404
- No order details disclosed

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/orders-and-purchasing.md:1-148`

## SCENARIO-0053 — Submit Order triggers automatic execution creation

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0058

**Preconditions:**
- Service order checkout is initiated

**Action:**
- Submit checkout transaction

**Expected observables:**
- Order created and CreateExecution internal command called within atomic transaction
- Execution created in received status with initial status changelog entry appended, visible on customer active orders and staff fulfillment queue

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/fulfillment.md:1-197`

## SCENARIO-0054 — Valid status transition updates history and sends notification

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0059

**Preconditions:**
- Staff member authenticated with executions.transition ability
- Execution in received status

**Action:**
- Staff transitions execution status to under_review

**Expected observables:**
- HTTP 200 returned
- Status updated to under_review
- Immediate status update on customer portal
- ExecutionStatusHistory appended
- Audit log recorded
- ExecutionStatusChanged notification in Outbox

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/fulfillment.md:1-197`

## SCENARIO-0055 — Reject invalid execution status transition

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0059

**Preconditions:**
- Execution is in received status

**Action:**
- Staff attempts to transition execution directly to completed

**Expected observables:**
- HTTP 422 error response with code execution.invalid_transition
- Execution status remains received
- No history or audit entries appended

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/fulfillment.md:1-197`

## SCENARIO-0056 — Staff requests customer action with customer-facing instruction

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0060

**Preconditions:**
- Execution is in under_review status
- Staff authenticated with executions.transition

**Action:**
- Staff submits RequestCustomerAction command with instruction text 'Please upload updated passport scan'

**Expected observables:**
- Open CustomerActionRequest created and execution status set to action_required
- Order card on customer dashboard displays banner 'Action Required From You' with exact instructions
- CustomerActionRequested notification dispatched

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/fulfillment.md:1-197`

## SCENARIO-0057 — Customer responds to action request and execution automatically resumes

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0061

**Preconditions:**
- Customer authenticated
- Execution owned by customer in action_required status with open action request

**Action:**
- Customer submits SubmitCustomerActionResponse with required document ID and notes

**Expected observables:**
- CustomerActionRequest marked resolved
- Execution status automatically updated to action_received
- Customer UI status reflects 'Requested Action Received'
- Fulfillment staff queue alerted
- CustomerActionReceived notification in Outbox

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/fulfillment.md:1-197`

## SCENARIO-0058 — Reject action response when no action request is pending

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0061

**Preconditions:**
- Execution is in processing status (no open action request)

**Action:**
- Customer attempts to call SubmitCustomerActionResponse

**Expected observables:**
- HTTP 409 Conflict with error code execution.action_not_pending
- Execution status unchanged

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/fulfillment.md:1-197`

## SCENARIO-0059 — Complete execution with clean issued document

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0062

**Preconditions:**
- Execution in processing status
- Staff uploaded clean issued visa document PDF

**Action:**
- Staff calls CompleteExecution with clean document ID

**Expected observables:**
- Status transitions to completed
- Issued document linked to execution
- Customer receives completion alert
- Order marked Completed
- Customer can download issued visa PDF via signed temp URL or authorized streaming

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/fulfillment.md:1-197`

## SCENARIO-0060 — Attempt to complete execution without clean document fails

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0062

**Preconditions:**
- Execution in processing status
- No issued document uploaded

**Action:**
- Staff calls CompleteExecution without valid document ID

**Expected observables:**
- HTTP 422 with code execution.missing_issued_document
- Execution status remains processing

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/fulfillment.md:1-197`

## SCENARIO-0061 — Cancel execution without altering wallet balance

**Kind:** surface
**Proof seam:** integration
**Owning stories:** STORY-0063

**Preconditions:**
- Execution in non-terminal state under_review
- Staff authenticated with executions.transition

**Action:**
- Staff submits CancelExecution with reason 'Customer requested cancellation via support'

**Expected observables:**
- Status transitions to cancelled
- Audit log appended
- ExecutionCancelled notification dispatched
- Wallet balance is NOT modified
- Customer and staff UI reflects cancelled execution status

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/fulfillment.md:1-197`

## SCENARIO-0062 — Staff internal notes privacy check

**Kind:** contract
**Proof seam:** app-level
**Owning stories:** STORY-0063

**Preconditions:**
- Execution exists
- Staff added internal note via AddInternalNote

**Action:**
- Customer queries execution details via customer API

**Expected observables:**
- HTTP 200 OK returned with execution details
- Internal notes field is completely omitted and hidden from customer API response payload

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/fulfillment.md:1-197`

## SCENARIO-0063 — Enforce single active draft per service

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0066

**Preconditions:**
- Active draft already exists for service visa-uae

**Action:**
- User with forms.draft calls CreateFormDraft for visa-uae

**Expected observables:**
- Request rejected because an active draft already exists for the service
- No second draft created

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/application-forms.md:1-132`

## SCENARIO-0064 — Draft modifications remain invisible to customers

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0066

**Preconditions:**
- Service has published FormVersion 1 and an active draft with new fields

**Action:**
- Customer queries GET /api/v1/services/{service}/application-form

**Expected observables:**
- HTTP 200 OK returning FormVersion 1 schema
- Un-published draft fields are completely invisible in customer response payload

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/application-forms.md:1-132`

## SCENARIO-0065 — Publish form version computes SHA-256 hash and archives draft

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0067

**Preconditions:**
- Valid form draft exists
- User has forms.publish ability

**Action:**
- User calls PublishFormVersion

**Expected observables:**
- FormVersion created with incremented version number and computed SHA-256 schema checksum
- Draft marked archived
- GET /api/v1/services/{service}/application-form immediately returns the new version
- FormVersionPublished event emitted
- Audit log written

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/application-forms.md:1-132`

## SCENARIO-0066 — Detect stale form version submission at checkout

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0068

**Preconditions:**
- Service published FormVersion 2
- Customer opened checkout form while FormVersion 1 was active

**Action:**
- Customer submits answers referencing FormVersion ID 1

**Expected observables:**
- HTTP 409 Conflict with code form.version_outdated and ID of authoritative version 2
- Checkout process halts and customer UI displays instruction to refresh the form

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/application-forms.md:1-132`

## SCENARIO-0067 — Historical orders preserve original form version snapshot

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0068

**Preconditions:**
- Order completed under FormVersion 1
- Service subsequently published FormVersion 2

**Action:**
- System or Staff retrieves historical order details

**Expected observables:**
- Historical order retains exact FormVersion 1 reference and raw submitted responses
- Historical order rendering is unchanged by schema modifications in FormVersion 2

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/application-forms.md:1-132`

## SCENARIO-0068 — Successful document upload returns pending_scan status

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0070

**Preconditions:**
- Customer authenticated

**Action:**
- Customer uploads valid PDF file 'passport.pdf' (5 MB)

**Expected observables:**
- HTTP 201 Created returned with document_id, filename, size, and status pending_scan
- Document file stored in private staging and async scanning scheduled

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/documents.md:1-148`

## SCENARIO-0069 — Reject upload exceeding size quota

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0070

**Preconditions:**
- Customer authenticated

**Action:**
- Customer attempts to upload a 15 MB JPEG image

**Expected observables:**
- HTTP 413 response with code document.file_too_large
- File rejected and not saved to storage

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/documents.md:1-148`

## SCENARIO-0070 — Reject prohibited file extension and tampered magic bytes

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0070

**Preconditions:**
- Customer authenticated

**Action:**
- Customer attempts to upload executable file 'script.exe' renamed to 'photo.png'

**Expected observables:**
- Magic bytes validator detects non-PNG signature and returns HTTP 422 document.unsupported_type
- Upload rejected immediately

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/documents.md:1-148`

## SCENARIO-0071 — Document scan detects malware and transitions to quarantine rejected

**Kind:** surface
**Proof seam:** integration
**Owning stories:** STORY-0071

**Preconditions:**
- Document record exists in pending_scan status

**Action:**
- ProcessDocumentScan background action runs on infected file

**Expected observables:**
- Malware engine flags file
- Status updated to rejected
- File placed in quarantine retention (30 days), cannot be attached to any order

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/documents.md:1-148`

## SCENARIO-0072 — Reject entity attachment of non-clean document

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0071

**Preconditions:**
- Document is in pending_scan or rejected status

**Action:**
- Customer submits SubmitOrder or SubmitTopUp linking the document ID

**Expected observables:**
- Parent checkout transaction aborts with HTTP 422 document.invalid_attachment
- Order or Top-Up submission fails, document remains unattached

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/documents.md:1-148`

## SCENARIO-0073 — Stream private document content with security headers

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0072

**Preconditions:**
- Document attached to customer order
- Caller is owning customer

**Action:**
- Customer requests StreamDocumentContent for document ID

**Expected observables:**
- HTTP 200 OK with binary stream and security headers Content-Disposition, X-Content-Type-Options: nosniff, Cache-Control: private, no-store
- Document content streamed securely without public exposure

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/documents.md:1-148`

## SCENARIO-0074 — Reject unauthorized document streaming access

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0072

**Preconditions:**
- Document belongs to Customer A

**Action:**
- Unauthenticated user or Customer B requests StreamDocumentContent for Customer A document ID

**Expected observables:**
- HTTP 403 Forbidden or HTTP 404 Not Found response
- File content is not streamed

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/documents.md:1-148`

## SCENARIO-0075 — Scheduled pruning job deletes unattached 24-hour-old documents

**Kind:** surface
**Proof seam:** process-level
**Owning stories:** STORY-0073

**Preconditions:**
- Document upload in clean status remained unattached for 25 hours
- Another document is in attached status

**Action:**
- PruneOrphanDocuments cron job executes

**Expected observables:**
- Unattached 25-hour-old document storage blob deleted and record marked expunged
- Unattached document permanently removed; attached document remains completely untouched

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/documents.md:1-148`

## SCENARIO-0076 — Atomic Outbox Message Enqueue Within Business Transaction

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0074

**Preconditions:**
- Business domain executes transaction (e.g. topup.approved)

**Action:**
- Execute business domain state update with AppendOutboxMessage call inside DB transaction
- Commit database transaction

**Expected observables:**
- in_app_notifications record created with status unread
- outbox_messages record created with status available
- Zero external network I/O executed during transaction
- In-app notification becomes immediately visible to customer
- outbox_messages table contains record with status 'available' and unique deduplication_key
- in_app_notifications table contains unread record for target customer

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/notifications.md:1-163`

## SCENARIO-0077 — Outbox Worker Batch Claiming and Locking

**Kind:** surface
**Proof seam:** integration
**Owning stories:** STORY-0074

**Preconditions:**
- Outbox messages exist with status='available' and available_at <= NOW()

**Action:**
- Execute ClaimOutboxBatch worker process

**Expected observables:**
- Queries outbox_messages using FOR UPDATE SKIP LOCKED up to limit 50
- Matched records updated to status='locked', locked_at=NOW(), locked_by=worker_id
- attempts incremented by 1
- Matched outbox records are locked by worker without row contention across concurrent processes

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/notifications.md:1-163`

## SCENARIO-0078 — Exponential Backoff Retry on Notification Delivery Failure

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0074

**Preconditions:**
- Outbox message is locked by worker and attempts < 5

**Action:**
- Trigger HandleDeliveryFailure after network dispatch fails (attempts = 1)

**Expected observables:**
- Message status reset to 'available'
- available_at updated to NOW() + (2^1 * 15 seconds) = NOW() + 30s
- Message is scheduled for retry after exponential backoff delay

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/notifications.md:1-163`

## SCENARIO-0079 — Dead-Letter Containment After Maximum Retry Exceeded

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0074

**Preconditions:**
- Outbox message has failed 5 times (attempts >= 5)

**Action:**
- Trigger HandleDeliveryFailure on 5th failure

**Expected observables:**
- Message status updated to 'dead_letter'
- Error logged to Audit trail
- Monitoring alert triggered
- Worker queue process continues operating without halting
- Message contained in dead_letter status and audit entry created

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/notifications.md:1-163`

## SCENARIO-0080 — Worker Crash Recovery Unlocking Expired Outbox Leases

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0074

**Preconditions:**
- Outbox message locked by worker process that crashed > 60 seconds ago

**Action:**
- Execute Recovery Scheduler process

**Expected observables:**
- Identifies expired locked message leases
- Unlocks expired messages by setting status back to 'available'
- Expired leased outbox message is re-eligible for worker claim

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/notifications.md:1-163`

## SCENARIO-0081 — Customer In-App Notifications Retrieval and Read Status Update

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0075

**Preconditions:**
- Customer is authenticated and has unread in-app notifications

**Action:**
- Customer calls ListCustomerNotifications API
- Customer calls MarkNotificationRead for a notification ID
- Customer calls MarkNotificationRead again for same notification ID

**Expected observables:**
- Returns paginated notification list and accurate unread count scoped to customer
- Notification status changes to 'read' and read_at timestamp set
- Operation succeeds idempotently without error
- Notification is marked read in DB and unread count decremented

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/notifications.md:1-163`

## SCENARIO-0082 — Atomic Audit Logging and Enclosing Business Transaction Rollback

**Kind:** contract
**Proof seam:** integration
**Owning stories:** STORY-0076

**Preconditions:**
- Sensitive business operation initiated (e.g. top-up approval)

**Action:**
- Execute sensitive operation with AppendAuditEntry inside DB transaction
- Simulate failure during audit entry insertion

**Expected observables:**
- Audit entry inserted with actor_id, correlation ID, and scrubbed state snapshot
- Enclosing business transaction fails and rolls back completely
- No state change persisted if audit entry insertion fails

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/audit.md:1-102`

## SCENARIO-0083 — Audit Log Immutability Database Trigger Protection

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0076

**Preconditions:**
- Audit log entries exist in database

**Action:**
- Attempt direct UPDATE or DELETE query on audit log table

**Expected observables:**
- Database trigger intercepts operation
- Raises exception 'Audit entries are immutable and cannot be updated.'
- Aborts query with SQLSTATE error code
- Audit entry table remains unmodified

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/audit.md:1-102`

## SCENARIO-0084 — Staff Audit Log Query Authorization and MFA Enforcement

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0077

**Preconditions:**
- Audit records exist in database

**Action:**
- Staff member with audit.view ability and active verified TOTP MFA session queries audit log
- Staff member without audit.view ability or without active MFA session attempts to query audit log

**Expected observables:**
- Returns filtered, paginated chronological event trail
- Returns HTTP 403 audit.access_denied
- Audit entries accessible only under verified MFA and authorized role

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/audit.md:1-102`

## SCENARIO-0085 — Platform Overview Metrics Computation and Timezone Handling

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0078

**Preconditions:**
- Staff user has admin.overview.view ability

**Action:**
- Query GetPlatformOverviewMetrics for custom cohort date range

**Expected observables:**
- Date boundaries evaluated strictly in Africa/Khartoum (UTC+2)
- Returns aggregated KPIs M01 through M12
- Zero denominators return 0 or 0.00% safely without division-by-zero errors
- No write operations performed on source tables
- Administrative dashboard widgets populated with accurate, reproducible KPI snapshot

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/reporting.md:1-116`

## SCENARIO-0086 — Reporting Validation and Zero-Activity Launch Day Safety

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0078

**Preconditions:**
- Platform environment operating on launch day or with invalid parameters

**Action:**
- Query GetPlatformOverviewMetrics with start date after end date
- Query GetPlatformOverviewMetrics on launch day with zero activity

**Expected observables:**
- Returns HTTP 422 reporting.invalid_date_range
- Returns metrics structure with 0 and 0.00% rates without throwing errors
- Validation errors handled cleanly and zero-data states gracefully represented

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/reporting.md:1-116`

## SCENARIO-0087 — Content Page Creation and Duplicate Slug Validation

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0080

**Preconditions:**
- Staff authenticated with content.manage ability

**Action:**
- Create content page with unique slug, EN title, AR title, EN body, and AR body
- Attempt to create second content page using identical slug

**Expected observables:**
- Draft content page created successfully
- Returns HTTP 422 content.slug_exists
- Content page stored in draft state with unique slug constraint enforced

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/content.md:1-107`

## SCENARIO-0088 — Bilingual Content Publishing and Public Layout Delivery

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0080, STORY-0081

**Preconditions:**
- Content page exists in draft state with non-empty EN and AR bodies

**Action:**
- Call PublishContentPage
- Public visitor calls GetPublicContentPage with locale=ar

**Expected observables:**
- Page status changed to published and published_at timestamp set
- Returns Arabic title and body
- Applies dir='rtl' layout rules
- Sanitizes HTML content (strips script, iframe, event handlers)
- Published page accessible to public with locale-appropriate layout and XSS protection

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/content.md:1-107`

## SCENARIO-0089 — Draft Content Isolation and Locale Fallback Handling

**Kind:** surface
**Proof seam:** app-level
**Owning stories:** STORY-0081

**Preconditions:**
- Draft content page exists and static UI strings registered

**Action:**
- Public guest attempts GetPublicContentPage on a draft page slug
- Public guest requests page with missing Arabic static UI string

**Expected observables:**
- Returns HTTP 404 content.page_not_found
- System falls back to English string without throwing HTTP 500 error
- Draft pages hidden from public view and missing translations fall back gracefully

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/domains/content.md:1-107`

## SCENARIO-0090 — Deactivated service slug returns 404 without CTAs

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0082

**Preconditions:**
- Service 'old-expired-visa' is in inactive status

**Action:**
- User navigates to GET /services/old-expired-visa via saved bookmark

**Expected observables:**
- HTTP 404 Not Found returned
- Page shows 'This service is currently unavailable for ordering'
- No Order Now or WhatsApp button rendered
- No order or session created

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-01-service-discovery-and-inquiry.md:1-114`

## SCENARIO-0091 — Registration rejected for duplicate email with zero state creation

**Kind:** failure-recovery
**Proof seam:** app-level
**Owning stories:** STORY-0035

**Preconditions:**
- Account for 'mohammed.osman@example.com' already exists in database

**Action:**
- User submits registration with email 'Mohammed.Osman@example.com' (mixed case)

**Expected observables:**
- Email normalized to lowercase matches existing account
- HTTP 422 returned with message 'An account with this email address already exists.'
- Email field highlighted in red
- No user record, wallet, or session created
- Zero records created
- Original account unchanged

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/journeys/journey-02-account-registration-and-profile.md:1-140`

## SCENARIO-0092 — Passport normalization strips whitespace, hyphens, and uppercases

**Kind:** surface
**Proof seam:** unit
**Owning stories:** STORY-0039

**Preconditions:**

**Action:**
- Call normalizePassport with input ' p 012-345 67 '
- Call normalizePassport with input 'P-012-345-67'

**Expected observables:**
- Returns 'P01234567'
- Returns 'P01234567'
- All PAS-01 to PAS-05 cases normalize correctly

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/passport-normalization-and-validation.md:1-77`

## SCENARIO-0093 — Passport boundary length validation

**Kind:** failure-recovery
**Proof seam:** unit
**Owning stories:** STORY-0039

**Preconditions:**

**Action:**
- Call normalizePassport with input 'A12345' (6 chars)
- Call normalizePassport with input 'P1234' (5 chars after normalization)
- Call normalizePassport with input 'P1234567890123' (13 chars)

**Expected observables:**
- Returns 'A12345' as valid
- Throws InvalidArgumentException with message 'Passport number must be between 6 and 12 alphanumeric characters.'
- Throws InvalidArgumentException for exceeding max length
- Length boundary enforcement confirmed for 5/6/12/13 char cases

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/passport-normalization-and-validation.md:1-77`

## SCENARIO-0094 — Global passport uniqueness collision detection across accounts

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0039

**Preconditions:**
- account_id=10 has registered traveler with normalized passport 'P01234567'

**Action:**
- account_id=20 attempts to register traveler with passport ' p-012 345 67 '

**Expected observables:**
- Normalization yields 'P01234567'
- Collision detected against existing DB record
- HTTP 422 returned with code traveler.passport_conflict
- No account ownership details disclosed in response
- Duplicate rejected; original record unchanged; UNI-02 and UNI-03 scenarios covered

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/passport-normalization-and-validation.md:1-77`

## SCENARIO-0095 — SDG monetary formatting and overdraft prevention

**Kind:** surface
**Proof seam:** unit
**Owning stories:** STORY-0046

**Preconditions:**

**Action:**
- Call formatSdg(500000, 'en')
- Call formatSdg(500000, 'ar')
- Call formatSdg(-1, 'en')
- Attempt DebitWallet with amount_minor 2500001 against balance 2500000

**Expected observables:**
- Returns '5,000.00 SDG'
- Returns '5,000.00 ج.س'
- Throws InvalidArgumentException 'Monetary minor units cannot be negative.'
- Returns wallet.insufficient_balance
- Balance remains 2500000
- MON-01, MON-07, MON-12 test vectors pass

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/money-and-minor-units.md:1-52`

## SCENARIO-0096 — Idempotency key replay returns cached order without re-debit

**Kind:** surface
**Proof seam:** integration
**Owning stories:** STORY-0057

**Preconditions:**
- Order ORD-1001 exists for account acc_10 with Idempotency-Key 'key-alpha' and payload fingerprint hash('svc1:trav12:2500000:v4')

**Action:**
- Submit POST /api/v1/order-submissions with Idempotency-Key: key-alpha and identical payload

**Expected observables:**
- Server locks idempotency record
- Payload fingerprint matches
- Returns HTTP 200 OK with cached ORD-1001
- Zero additional wallet debit
- IDP-02 test vector: wallet debited exactly once

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/idempotency-and-deduplication.md:1-52`

## SCENARIO-0097 — Idempotency key conflict on payload mismatch

**Kind:** failure-recovery
**Proof seam:** integration
**Owning stories:** STORY-0057

**Preconditions:**
- Order ORD-1001 exists for account acc_10 with Idempotency-Key 'key-alpha'

**Action:**
- Submit POST with Idempotency-Key: key-alpha but different traveler ID (trav99)

**Expected observables:**
- Payload fingerprint mismatch detected
- HTTP 409 Conflict with code order.idempotency_conflict
- No debit, no new order created
- IDP-03 test vector: conflict detected and rejected

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/idempotency-and-deduplication.md:1-52`

## SCENARIO-0098 — Bank reference uniqueness per bank account and cross-bank independence

**Kind:** surface
**Proof seam:** integration
**Owning stories:** STORY-0051

**Preconditions:**
- Bank #1 has existing top-up with normalized reference 'BOK123456'

**Action:**
- Submit top-up to Bank #1 with reference ' bok-123-456 '
- Submit top-up to Bank #2 with reference 'BOK-123456'

**Expected observables:**
- Normalizes to 'BOK123456'
- Collision detected for Bank #1
- Returns top_up.reference_used
- Normalizes to 'BOK123456'
- No collision for Bank #2 (different bank_account_id)
- Accepted
- REF-02 rejected; REF-04 accepted; bank-scoped uniqueness confirmed

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/idempotency-and-deduplication.md:1-52`

## SCENARIO-0099 — Form field type validation across all 11 supported types

**Kind:** surface
**Proof seam:** unit
**Owning stories:** STORY-0069

**Preconditions:**
- Published FormVersion with all 11 field types declared

**Action:**
- Submit email field with 'not-an-email'
- Submit number field with string 'three'
- Submit dropdown field with option 'other' not in declared options
- Submit phantom field 'favorite_color' not declared in schema

**Expected observables:**
- Returns HTTP 422 with form.invalid_email_format
- Returns HTTP 422 with form.numeric_expected
- Returns HTTP 422 with form.invalid_option_selected
- Returns HTTP 422 with form.undeclared_field_rejected
- FRM-06, FRM-10, FRM-15, FRM-24 test vectors covered

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/form-schema-validation-and-evaluation.md:1-57`

## SCENARIO-0100 — Execution state machine invalid transitions rejected

**Kind:** failure-recovery
**Proof seam:** unit
**Owning stories:** STORY-0065

**Preconditions:**

**Action:**
- Attempt TransitionStatus from received to completed (EXE-TR11)
- Attempt TransitionStatus from completed to processing (EXE-TR13)
- Attempt CompleteExecution without issued document attached (EXE-TR16)
- Attempt RequestCustomerAction with empty instructions (EXE-TR17)

**Expected observables:**
- HTTP 422 execution.invalid_transition
- HTTP 422 execution.already_terminal
- HTTP 422 execution.missing_issued_document
- HTTP 422 execution.missing_action_instructions
- EXE-TR11, EXE-TR13, EXE-TR16, EXE-TR17 test vectors covered

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/state-machines-and-transitions.md:1-55`

## SCENARIO-0101 — Reporting metrics calculation accuracy from benchmark fixture

**Kind:** surface
**Proof seam:** unit
**Owning stories:** STORY-0079

**Preconditions:**
- Benchmark fixture loaded: 3 users, 4 travelers, 4 top-ups (3 decided), 3 orders, 3 executions (2 completed)

**Action:**
- Query GetPlatformOverviewMetrics for benchmark cohort date 2026-09-11 in Africa/Khartoum timezone

**Expected observables:**
- M01=3
- M02=4
- M03-A=3
- M03-B='90,000.00 SDG'
- M04='75.00%'
- M05='40.00 minutes'
- M06='66.67%'
- M07: UAE Visa 2 orders, Saudi Visa 1 order
- M08='7.00 hours'
- M09=1
- M10='66.67%'
- M11='50.00%'
- M12='50.00%'
- All M01-M12 metric calculations match expected formatted outputs from benchmark fixture

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/reporting-metrics-calculations.md:1-87`

## SCENARIO-0102 — Reporting metrics division-by-zero safety on launch day

**Kind:** failure-recovery
**Proof seam:** unit
**Owning stories:** STORY-0079

**Preconditions:**
- No top-ups, orders, or executions exist

**Action:**
- Query all metrics M04-M12 with zero denominators

**Expected observables:**
- ZER-01: M04 = '0.00%'
- ZER-02: M05 = '0.00 minutes'
- ZER-03: M06 = '0.00%'
- ZER-04: M08 = '0.00 hours'
- ZER-05: M10 = '0.00%'
- ZER-06: M11 = '0.00%'
- ZER-07: M12 = '0.00%'
- No exceptions or null values thrown
- All ZER-01 to ZER-07 test vectors produce safe zero outputs without errors

**Automation status:** pending
**Execution command:** TBD

**Sources:**
- `specs/test-vectors/reporting-metrics-calculations.md:1-87`
