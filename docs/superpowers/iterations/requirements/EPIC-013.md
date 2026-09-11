# EPIC-013 — Fulfillment

**Summary:** Fulfillment
**Stories:** STORY-0058, STORY-0059, STORY-0060, STORY-0061, STORY-0062, STORY-0063, STORY-0064, STORY-0065
**Primary sources:** `specs/domains/fulfillment.md`, `specs/journeys/journey-06-execution-tracking-and-customer-action.md`, `specs/test-vectors/state-machines-and-transitions.md`
**Status:** 0/8 done

## STORY-0058

**Epic:** EPIC-013 — Fulfillment
**Title:** Internal Execution Creation

**As a** System Purchasing Domain
**I want** to create a service execution record when an order is submitted
**So that** operational fulfillment tracking can begin independently from payment state

**Acceptance criteria:**
- AC-1: Decouple operational fulfillment status from financial payment status. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0053`
- AC-2: Create new execution record in 'received' status with initial status changelog entry appended during atomic SubmitOrder transaction. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0053`
- AC-3: Display newly created execution on customer's active orders list and staff fulfillment queue. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0053`

**Sources:**
- `specs/domains/fulfillment.md:1-197`

**Status:** pending

## STORY-0059

**Epic:** EPIC-013 — Fulfillment
**Title:** Staff Execution Status Transitions

**As a** Fulfillment Staff Member
**I want** to transition an execution's status according to a deterministic state machine
**So that** service fulfillment progresses predictably through its lifecycle

**Acceptance criteria:**
- AC-1: Require executions.transition ability and validate target transition against the 7-state execution lifecycle graph. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0054`
- AC-2: Atomically lock execution row, update status and updated_at, record ExecutionStatusHistory, write Audit log, and dispatch ExecutionStatusChanged notification. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0054`
- AC-3: Reflect immediate status update on customer portal and send notification to customer. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0054`
- AC-4: Reject invalid state transition attempts with HTTP 422 execution.invalid_transition. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0054`

**Sources:**
- `specs/domains/fulfillment.md:1-197`

**Status:** pending

## STORY-0060

**Epic:** EPIC-013 — Fulfillment
**Title:** Staff Request Customer Action

**As a** Fulfillment Staff Member
**I want** to request additional action or information from the customer on an execution in under_review or processing
**So that** customer input can be collected with explicit instructions

**Acceptance criteria:**
- AC-1: Require explicit non-empty customer-facing instruction when transitioning to action_required. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0056`
- AC-2: Create open CustomerActionRequest, set execution status to action_required, and dispatch CustomerActionRequested notification. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0056`
- AC-3: Display 'Action Required From You' (EN) / 'مطلوب إجراء منك' (AR) banner with staff instructions on customer order card. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0056`

**Sources:**
- `specs/domains/fulfillment.md:1-197`

**Status:** pending

## STORY-0061

**Epic:** EPIC-013 — Fulfillment
**Title:** Customer Submit Action Response

**As a** Customer
**I want** to submit required information or clean documents for an open action request
**So that** service fulfillment can automatically resume

**Acceptance criteria:**
- AC-1: Authenticate customer, verify execution ownership, and ensure open action request exists in action_required status. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0057`
- AC-2: Mark CustomerActionRequest resolved, automatically transition execution status to action_received, and dispatch CustomerActionReceived notification. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0057`
- AC-3: Alert fulfillment staff queue and immediately update customer interface to show 'Requested Action Received'. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0057`
- AC-4: Reject response submission if no action is pending with HTTP 409 execution.action_not_pending. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0057`

**Sources:**
- `specs/domains/fulfillment.md:1-197`

**Status:** pending

## STORY-0062

**Epic:** EPIC-013 — Fulfillment
**Title:** Execution Completion and Issued Document Access

**As a** Fulfillment Staff Member
**I want** to complete an execution by uploading a clean issued document
**So that** the customer can receive their completed service and download issued documents

**Acceptance criteria:**
- AC-1: Require staff executions.transition ability and execution in under_review, processing, or action_received with clean uploaded document. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0059`
- AC-2: Link issued document, transition execution status to completed, and dispatch ExecutionCompleted notification. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0059`
- AC-3: Send completion alert to customer, update order state to 'Completed', and provide download access to issued document stored on private disk via authorized streaming or signed temp URLs. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0059`
- AC-4: Reject completion attempt if issued document is missing or not clean with HTTP 422 execution.missing_issued_document. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0059`

**Sources:**
- `specs/domains/fulfillment.md:1-197`

**Status:** pending

## STORY-0063

**Epic:** EPIC-013 — Fulfillment
**Title:** Execution Cancellation and Internal Notes

**As a** Fulfillment Staff Member
**I want** to cancel an execution with a mandatory reason or append internal notes
**So that** operational non-fulfillments are documented without automated financial side effects or customer leakage

**Acceptance criteria:**
- AC-1: Require staff executions.transition, non-terminal status, and mandatory cancellation reason (min 15 chars) to set status to cancelled, log audit entry, and dispatch ExecutionCancelled notification without modifying wallet balance. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0061`
- AC-2: Reject cancellation of already completed or cancelled executions with execution.already_terminal. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0061`
- AC-3: Allow staff with executions.note to add internal notes linked to execution while strictly hiding internal notes from customer queries and APIs. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0061`

**Sources:**
- `specs/domains/fulfillment.md:1-197`

**Status:** pending

## STORY-0064

**Epic:** EPIC-013 — Fulfillment
**Title:** Execution Tracking and Customer Action Response Journey

**As a** Customer and Staff
**I want** to track fulfillment progress through status transitions, respond to action requests from staff, and download the issued visa PDF upon completion
**So that** the service delivery lifecycle completes successfully with full traceability

**Acceptance criteria:**
- AC-1: Fulfillment officer transitions status from received → under_review → processing, appending each transition to status history and writing audit entry. · impact:`local` · seam:`integration` · scenario:`JOURNEY-0006`
- AC-2: Staff creates CustomerActionRequest transitioning execution to action_required; customer dashboard prominently displays 'Action Required From You' (EN) / 'مطلوب إجراء منك' (AR) banner with exact instructions. · impact:`cross-surface` · seam:`app-level` · scenario:`JOURNEY-0006`
- AC-3: Customer submits response with clean document; execution auto-transitions to action_received; notification dispatched; staff queue alerted. · impact:`cross-surface` · seam:`app-level` · scenario:`JOURNEY-0006`
- AC-4: Staff completes execution with clean issued visa PDF linked; order badge shows 'Completed'; customer receives completion notification and can download visa PDF via authorized Content-Disposition stream. · impact:`cross-surface` · seam:`app-level` · scenario:`JOURNEY-0006`
- AC-5: Cancellation by staff sets execution to cancelled, writes audit entry with mandatory reason, dispatches ExecutionCancelled notification, and does NOT modify wallet balance. · impact:`local` · seam:`integration` · scenario:`JOURNEY-0006`

**Sources:**
- `specs/journeys/journey-06-execution-tracking-and-customer-action.md:1-168`

**Status:** pending

## STORY-0065

**Epic:** EPIC-013 — Fulfillment
**Title:** Execution State Machine Transition Matrix Enforcement

**As a** System State Machine Engine
**I want** to enforce the complete 7-state execution lifecycle and top-up 2-state transition matrix
**So that** arbitrary, backward, or invalid transitions are rejected with precise error codes

**Acceptance criteria:**
- AC-1: EXE-TR01 to EXE-TR08: All valid forward transitions and conditional transitions (action_required, action_received, completed) pass. · impact:`none` · seam:`unit`
- AC-2: EXE-TR09/EXE-TR10: Cancellation from received and processing with mandatory reason passes. · impact:`none` · seam:`unit`
- AC-3: EXE-TR11: Skipping from received to completed returns execution.invalid_transition. · impact:`none` · seam:`unit`
- AC-4: EXE-TR12: Customer cannot transition to action_received without a prior action request. · impact:`none` · seam:`unit`
- AC-5: EXE-TR13 to EXE-TR15: Transitions from terminal states (completed, cancelled) return execution.already_terminal. · impact:`none` · seam:`unit`
- AC-6: EXE-TR16: Completion without issued document returns execution.missing_issued_document. · impact:`none` · seam:`unit`
- AC-7: EXE-TR17: Transition to action_required with empty instructions returns execution.missing_action_instructions. · impact:`none` · seam:`unit`
- AC-8: TOP-TR01/TOP-TR02: Top-up transitions under_review → approved and under_review → rejected (with reason) pass. · impact:`none` · seam:`unit`
- AC-9: TOP-TR03 to TOP-TR05: Transitions from terminal top-up states return top_up.already_terminal. · impact:`none` · seam:`unit`
- AC-10: TOP-TR06: Rejection with empty reason returns top_up.missing_rejection_reason. · impact:`none` · seam:`unit`

**Sources:**
- `specs/test-vectors/state-machines-and-transitions.md:1-55`

**Status:** pending