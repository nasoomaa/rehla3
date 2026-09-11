# EPIC-010 — Wallet & Financial Ledger

**Summary:** Wallet & Financial Ledger
**Stories:** STORY-0043, STORY-0044, STORY-0045, STORY-0046
**Primary sources:** `specs/domains/wallet-and-ledger.md`, `specs/test-vectors/money-and-minor-units.md`
**Status:** 0/4 done

## STORY-0043

**Epic:** EPIC-010 — Wallet & Financial Ledger
**Title:** Wallet Initialization and Balance Management

**As a** Customer
**I want** a wallet initialized upon account creation and to check my balance and ledger history
**So that** I can manage my funds in SDG for purchasing services

**Acceptance criteria:**
- AC-1: New wallet is initialized with balance=0, status=active, currency=SDG on customer registration, displaying initial balance of 0.00 SDG. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0037`
- AC-2: Wallet balance and ledger entries are formatted in SDG monetary format (e.g. 25,000.00 SDG). · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0037`
- AC-3: Frozen wallet rejects any debit or credit attempt with wallet.frozen (HTTP 403). · impact:`local` · seam:`integration` · scenario:`SCENARIO-0037`

**Sources:**
- `specs/domains/wallet-and-ledger.md:1-151`

**Status:** pending

## STORY-0044

**Epic:** EPIC-010 — Wallet & Financial Ledger
**Title:** Wallet Crediting and Debiting Operations

**As a** System Service
**I want** to credit or debit customer wallets atomically with pessimistic locking and immutable ledger entries
**So that** customer balances accurately reflect top-ups and purchases while preventing overdrafts and duplicate credits

**Acceptance criteria:**
- AC-1: DebitWallet locks wallet row with SELECT FOR UPDATE, verifies non-negative balance (current_balance_minor >= amount_minor), and decrements balance atomically with ledger entry insertion. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0038`
- AC-2: CreditWallet verifies idempotency reference uniqueness, locks wallet row, increments balance, inserts immutable WalletLedgerEntry, and notifies user. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0038`
- AC-3: Duplicate external reference for CreditWallet fails with wallet.duplicate_credit_reference (HTTP 409). · impact:`local` · seam:`integration` · scenario:`SCENARIO-0038`
- AC-4: Insufficient balance on DebitWallet rolls back transaction and fails with wallet.insufficient_balance (HTTP 422). · impact:`local` · seam:`integration` · scenario:`SCENARIO-0038`

**Sources:**
- `specs/domains/wallet-and-ledger.md:1-151`

**Status:** pending

## STORY-0045

**Epic:** EPIC-010 — Wallet & Financial Ledger
**Title:** Automated Ledger Reconciliation

**As a** System Administrator
**I want** scheduled reconciliation comparing wallet balance against ledger credits minus debits
**So that** financial discrepancies trigger critical alerts and automatically freeze affected wallets

**Acceptance criteria:**
- AC-1: RunReconciliation calculates computed_balance = SUM(credits) - SUM(debits) and logs pass when equal. · impact:`none` · seam:`integration`
- AC-2: When computed balance does not equal current balance, RunReconciliation triggers a critical alert, automatically freezes the wallet, and logs an error to Audit. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0041`

**Sources:**
- `specs/domains/wallet-and-ledger.md:1-151`

**Status:** pending

## STORY-0046

**Epic:** EPIC-010 — Wallet & Financial Ledger
**Title:** Monetary Minor Units Representation and Arithmetic Precision

**As a** System Financial Engine
**I want** to represent all SDG monetary values as 64-bit non-negative integers with scale 100, with correct formatting and overdraft prevention
**So that** floating-point arithmetic errors, negative balances, and monetary representation bugs are impossible

**Acceptance criteria:**
- AC-1: MON-01 to MON-03: Standard, fractional, and high-value minor units format correctly to SDG and Arabic ج.س currency strings. · impact:`local` · seam:`unit` · scenario:`SCENARIO-0095`
- AC-2: MON-04/MON-05: 500,000 minor units (exactly 5,000 SDG) passes minimum top-up threshold; 499,999 fails with top_up.below_minimum. · impact:`none` · seam:`unit`
- AC-3: MON-06/MON-07/MON-08: Zero, negative, and float type inputs are rejected with appropriate errors. · impact:`none` · seam:`unit`
- AC-4: MON-09/MON-10: Credit and debit arithmetic on integer minor units produces correct new balance without floating-point loss. · impact:`none` · seam:`unit`
- AC-5: MON-11: Exact balance exhaustion (balance drops to exactly 0) is permitted. · impact:`none` · seam:`unit`
- AC-6: MON-12: Overdraft attempt (debit 1 minor unit more than balance) returns wallet.insufficient_balance and leaves balance unchanged. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0095`

**Sources:**
- `specs/test-vectors/money-and-minor-units.md:1-52`

**Status:** pending