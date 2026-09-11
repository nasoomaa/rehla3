# EPIC-002 — Top-Up Management

**Summary:** Top-Up Management
**Stories:** STORY-0005, STORY-0006, STORY-0007
**Primary sources:** `specs/contracts/bank-transfer-receipt-contract.md`
**Status:** 0/3 done

## STORY-0005

**Epic:** EPIC-002 — Top-Up Management
**Title:** Submit Bank Transfer Receipt Proof for Top-Up Verification

**As a** Customer
**I want** to submit bank transfer details and uploaded receipt document IDs
**So that** my out-of-band payment can be reviewed and credited to my wallet

**Acceptance criteria:**
- AC-1: Submitting bank transfer claim validates active bank_account_id, transaction_reference, clean receipt_document_id, and amount_minor >= 500,000 (5,000.00 SDG). · impact:`local` · seam:`integration`
- AC-2: Top-up submission with amount below 500,000 minor units is rejected with HTTP 422 top_up.below_minimum. · impact:`local` · seam:`integration`
- AC-3: Top-up submission targeting an inactive bank account is rejected with HTTP 422 top_up.bank_account_inactive. · impact:`local` · seam:`integration`
- AC-4: Top-up submission with unverified or missing receipt document ID is rejected with HTTP 422 top_up.receipt_required. · impact:`local` · seam:`integration`

**Sources:**
- `specs/contracts/bank-transfer-receipt-contract.md:13-98`

**Status:** pending

## STORY-0006

**Epic:** EPIC-002 — Top-Up Management
**Title:** Normalize Transaction References and Prevent Duplicate Receipts

**As a** Financial System Manager
**I want** transaction references to be normalized and constrained to unique bank account pairs
**So that** accidental resubmissions and double-spending attempts are rejected immediately

**Acceptance criteria:**
- AC-1: Transaction reference is normalized by converting to uppercase and stripping all non-alphanumeric characters. · impact:`local` · seam:`unit` · scenario:`SCENARIO-0007`
- AC-2: Database constraint uq_bank_account_reference enforces uniqueness for (bank_account_id, normalized_reference), returning HTTP 422 top_up.reference_used on duplicate. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0007`

**Sources:**
- `specs/contracts/bank-transfer-receipt-contract.md:35-46`

**Status:** pending

## STORY-0007

**Epic:** EPIC-002 — Top-Up Management
**Title:** Verify and Execute Top-Up Review Decisions with Atomic Wallet Credit

**As a** Operations Reviewer
**I want** to execute verification checklist procedures and record approval or rejection outcomes
**So that** approved deposits atomically credit wallets while rejected deposits issue customer notifications without altering balances

**Acceptance criteria:**
- AC-1: Review checklist requires verifying beneficiary bank match, exact amount match, reference match, 72h timestamp plausibility, and internal statement reconciliation. · impact:`local` · seam:`process-level` · scenario:`SCENARIO-0001`
- AC-2: Approving top-up transitions status to approved, invokes CreditWallet with reference_type top_up_approval, increases balance, and dispatches TopUpApproved notification. · impact:`journey` · seam:`integration` · scenario:`SCENARIO-0001`
- AC-3: Rejecting top-up requires selecting or typing a standardized reason (receipt_unreadable, amount_mismatch, funds_not_received, invalid_reference), leaves wallet balance unchanged, and dispatches TopUpRejected notification. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0001`

**Sources:**
- `specs/contracts/bank-transfer-receipt-contract.md:48-87`

**Status:** pending