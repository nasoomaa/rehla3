# EPIC-001 — Administrative Control Plane

**Summary:** Administrative Control Plane
**Stories:** STORY-0001, STORY-0002, STORY-0003, STORY-0004
**Primary sources:** `specs/contracts/admin-operations-contract.md`
**Status:** 0/4 done

## STORY-0001

**Epic:** EPIC-001 — Administrative Control Plane
**Title:** Enforce Administrative Architectural Rules, Read-Only Models, and Ability Security

**As a** Security Officer
**I want** the admin panel to use read-only models for browsing and restrict state changes to domain DTOs with mandatory MFA
**So that** presentation layers cannot execute direct SQL writes or mutate immutable historical entities

**Acceptance criteria:**
- AC-1: List and detail views bind exclusively to read-only models forbidding save(), update(), delete(), or relationship mutation. · impact:`local` · seam:`integration`
- AC-2: Administrative presentation layer executes state modifications only through explicit Domain Action DTOs, prohibiting direct Eloquent or raw SQL write statements. · impact:`local` · seam:`integration`
- AC-3: Administrative UI components strictly omit 'Edit' or 'Delete' actions for immutable entities including wallet ledger entries, commercial orders, published form versions, and audit log entries. · impact:`local` · seam:`app-level`
- AC-4: Every administrative section, resource, and action is gated by explicit abilities with deny-by-default access control. · impact:`local` · seam:`integration`
- AC-5: Executing high-risk operations (top-up review, role assignment, audit log viewing) requires TOTP MFA verification within the last 4 hours. · impact:`cross-surface` · seam:`integration`

**Sources:**
- `specs/contracts/admin-operations-contract.md:12-46`

**Status:** pending

## STORY-0002

**Epic:** EPIC-001 — Administrative Control Plane
**Title:** Process Top-Up Approvals and Rejections via Domain Actions

**As a** Operations Reviewer
**I want** to approve or reject pending bank top-up requests using explicit domain actions
**So that** approved deposits credit customer wallets atomically while rejections record mandatory reasons and audit trails

**Acceptance criteria:**
- AC-1: ApproveTopUp action asserts staff has topups.review and active MFA, locks row, credits wallet, and appends to audit_entries. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0001`
- AC-2: Approving an already-approved top-up request returns an idempotency confirmation and handles TopUpAlreadyDecidedException by refreshing view and alerting staff. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0001`
- AC-3: RejectTopUp action mandates a rejection_reason of at least 10 characters, sets status to rejected, leaves wallet balance unchanged, and appends to audit_entries. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0001`

**Sources:**
- `specs/contracts/admin-operations-contract.md:48-78`

**Status:** pending

## STORY-0003

**Epic:** EPIC-001 — Administrative Control Plane
**Title:** Manage Service Execution State Machine Transitions

**As a** Operations Staff Member
**I want** to transition service execution cases through lifecycle states
**So that** cases progress validly through the 7-state lifecycle state machine with full audit notes

**Acceptance criteria:**
- AC-1: TransitionExecution verifies state machine validity before transitioning execution status and saving staff notes or attached document IDs. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0004`
- AC-2: Attempting an invalid execution state transition triggers InvalidExecutionTransitionException and displays a modal error indicating valid options. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0004`

**Sources:**
- `specs/contracts/admin-operations-contract.md:71-107`

**Status:** pending

## STORY-0004

**Epic:** EPIC-001 — Administrative Control Plane
**Title:** Mask Sensitive Fields and Protect Private Document Access

**As a** Compliance Officer
**I want** passport numbers masked by default and private document links streamed securely
**So that** customer PII is not leaked and document storage URLs are not publicly exposed

**Acceptance criteria:**
- AC-1: Passport numbers appear masked as P*****567 by default and require explicit travelers.view_sensitive ability and user action to unmask. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0005`
- AC-2: Accessing private document scans generates a temporary signed streaming URL valid for 15 minutes bound to staff IP/session, served with inline disposition and nosniff headers. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0005`

**Sources:**
- `specs/contracts/admin-operations-contract.md:80-97`

**Status:** pending