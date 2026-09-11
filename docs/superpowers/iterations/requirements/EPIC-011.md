# EPIC-011 — Top-Ups and Bank Transfers

**Summary:** Top-Ups and Bank Transfers
**Stories:** STORY-0047, STORY-0048, STORY-0049, STORY-0050, STORY-0051
**Primary sources:** `specs/domains/top-ups.md`, `specs/journeys/journey-04-wallet-topup-submission-and-review.md`, `specs/test-vectors/idempotency-and-deduplication.md`
**Status:** 0/5 done

## STORY-0047

**Epic:** EPIC-011 — Top-Ups and Bank Transfers
**Title:** Submit Top-Up Request

**As a** Customer
**I want** to submit bank transfer top-up requests with reference numbers and attached receipt documents
**So that** staff can review and credit my wallet balance

**Acceptance criteria:**
- AC-1: Enforce minimum top-up threshold of 500,000 minor units (5,000 SDG), rejecting lower amounts with HTTP 422 top_up.below_minimum. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0042`
- AC-2: Transaction reference is normalized by stripping whitespace, hyphens, and slashes, converted to uppercase, and checked for global uniqueness per bank account. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0042`
- AC-3: Receipt document must exist, belong to customer, and be clean, updating status to attached upon request creation in under_review status. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0042`
- AC-4: Customer top-up submission rate is limited to a maximum of 5 submissions per hour. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0042`

**Sources:**
- `specs/domains/top-ups.md:1-155`

**Status:** pending

## STORY-0048

**Epic:** EPIC-011 — Top-Ups and Bank Transfers
**Title:** Top-Up Request Review and Decision

**As a** Authorized Staff
**I want** to review, approve, or reject top-up requests with TOTP MFA authorization
**So that** wallet credits are issued safely for legitimate payments and bad requests are rejected with reasons

**Acceptance criteria:**
- AC-1: Approving top-up requires topups.review ability and active TOTP MFA authorization. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0044`
- AC-2: Top-up approval locks request row, invokes CreditWallet atomically, sets status to approved with reviewer_id and decision_at, appends Audit log, and enqueues TopUpApproved to Outbox. · impact:`journey` · seam:`integration` · scenario:`SCENARIO-0044`
- AC-3: Re-approving an already approved request returns existing success idempotently without double crediting; reviewing a decided request with conflicting action returns top_up.already_decided. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0044`
- AC-4: Rejecting top-up request requires mandatory reason (10-500 chars), sets status to rejected, appends Audit log, enqueues TopUpRejected to Outbox, and leaves wallet balance untouched. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0044`

**Sources:**
- `specs/domains/top-ups.md:1-155`

**Status:** pending

## STORY-0049

**Epic:** EPIC-011 — Top-Ups and Bank Transfers
**Title:** Bank Account Management for Top-Ups

**As a** Staff Administrator
**I want** to create, update, or deactivate payment bank accounts
**So that** customers see active payment destinations while historical top-up references remain intact

**Acceptance criteria:**
- AC-1: Staff with banks.manage ability can create or update platform bank account details. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0047`
- AC-2: Deactivating a bank account hides it from new customer top-up forms while preserving historical top-up records. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0047`

**Sources:**
- `specs/domains/top-ups.md:1-155`

**Status:** pending

## STORY-0050

**Epic:** EPIC-011 — Top-Ups and Bank Transfers
**Title:** Wallet Top-Up Submission and Administrative Approval Journey

**As a** Customer
**I want** to fund my wallet by submitting a bank transfer with a receipt and reference number, and have staff review and atomically credit my balance
**So that** I have sufficient wallet balance to purchase services

**Acceptance criteria:**
- AC-1: Customer selects active bank account, enters amount >= 500,000 minor units (5,000 SDG), provides normalized transaction reference, and attaches clean receipt document. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0004`
- AC-2: Top-up approval by staff with topups.review ability and active TOTP MFA executes an atomic transaction: locks top-up row, locks wallet row FOR UPDATE, calls CreditWallet, updates top-up to approved status with reviewer_id and decision_at, appends audit entry, and enqueues TopUpApproved to Outbox. · impact:`journey` · seam:`integration` · scenario:`JOURNEY-0004`
- AC-3: Upon approval, customer in-app notification is delivered: 'Your wallet top-up of X SDG via [Bank] has been approved.' and wallet balance is updated to show new balance. · impact:`cross-surface` · seam:`app-level` · scenario:`JOURNEY-0004`
- AC-4: Concurrent double-approval prevention: when two reviewers approve simultaneously, second reviewer sees approved status via row lock and returns existing result without double-crediting wallet. · impact:`local` · seam:`integration` · scenario:`JOURNEY-0004`
- AC-5: Rejection requires mandatory reason, sets status to rejected, appends audit log, enqueues TopUpRejected, and leaves wallet balance unchanged. · impact:`cross-surface` · seam:`app-level` · scenario:`JOURNEY-0004`

**Sources:**
- `specs/journeys/journey-04-wallet-topup-submission-and-review.md:1-197`

**Status:** pending

## STORY-0051

**Epic:** EPIC-011 — Top-Ups and Bank Transfers
**Title:** Bank Reference and Outbox Deduplication

**As a** System Deduplication Engine
**I want** to enforce per-bank-account uniqueness of normalized bank transaction references and prevent duplicate outbox event insertions
**So that** no double top-up requests or duplicate event deliveries occur

**Acceptance criteria:**
- AC-1: REF-01: First submission with new reference is accepted. · impact:`none` · seam:`unit`
- AC-2: REF-02/REF-03: Resubmission with same normalized reference to same bank account returns top_up.reference_used. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0098`
- AC-3: REF-04: Same normalized reference to a different bank account is accepted (uniqueness is scoped to bank_account_id). · impact:`local` · seam:`integration` · scenario:`SCENARIO-0098`
- AC-4: OUT-01/OUT-03: First outbox event insertion succeeds with status available. · impact:`none` · seam:`unit`
- AC-5: OUT-02/OUT-04: Duplicate outbox event with same deduplication_key triggers PostgreSQL unique constraint violation (23505) and blocks insertion. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0098`

**Sources:**
- `specs/test-vectors/idempotency-and-deduplication.md:1-52`

**Status:** pending