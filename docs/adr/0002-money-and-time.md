# ADR 0002: Monetary Arithmetic and Temporal Handling

## Status
Accepted

## Context
Financial accuracy and temporal consistency are paramount in travel booking, wallet management, and bank top-up verification. Float arithmetic induces rounding anomalies, and ambiguous timezones cause transaction sequence discrepancies.

## Decision
1. **Monetary Representation**:
   - Currency is exclusively `SDG` (Sudanese Pound) for Phase 1.
   - Scale is 100 (1 SDG = 100 piasters/minor units).
   - All amounts are stored and calculated as 64-bit integer minor units (`amount_minor` / `int`).
   - Use of `float` or `double` for currency is strictly prohibited across all packages, database columns, and DTOs.
   - Core provides an immutable `Money` value object (`Money::sdg(int $minor)`) enforcing non-negative values and arithmetic operations (`add`, `subtract`, `isLessThan`).
2. **Double-Entry Ledger Invariants**:
   - Wallet balances are backed by immutable double-entry ledger entries.
   - Debits cannot occur without corresponding Orders and Executions; Orders cannot execute without debit.
   - Overdrafts and negative wallet balances are prohibited at the database constraint level.
3. **Temporal Handling**:
   - All persistent timestamps must be stored in UTC (`TIMESTAMPTZ`).
   - Application clock is abstracted via `Clock` / `SystemClock` returning `CarbonImmutable`.
   - Reporting and customer facing interfaces format dates and operational metrics in the `Africa/Khartoum` timezone.

## Consequences
- Zero floating-point rounding errors.
- Unambiguous transaction ordering and regulatory audit compliance.
