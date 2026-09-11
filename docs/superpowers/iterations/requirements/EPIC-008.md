# EPIC-008 — Travelers

**Summary:** Travelers
**Stories:** STORY-0036, STORY-0037, STORY-0038, STORY-0039, STORY-0040
**Primary sources:** `specs/domains/travelers.md`, `specs/journeys/journey-03-traveler-profile-management.md`, `specs/test-vectors/passport-normalization-and-validation.md`
**Status:** 0/5 done

## STORY-0036

**Epic:** EPIC-008 — Travelers
**Title:** Add Traveler Profile

**As a** Authenticated Customer
**I want** to save traveler profiles with validated biographical details and normalized passport numbers
**So that** I can quickly select saved travelers during service checkout

**Acceptance criteria:**
- AC-1: Traveler profile requires full name (3-100 chars), date of birth in past, gender (male/female), passport issue date in past after DOB, and passport expiry date after issue date. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0029`
- AC-2: Passport number is normalized by stripping leading/trailing whitespace, removing spaces/hyphens/delimiters, uppercasing, and validating ^[A-Z0-9]{6,12}$. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0029`
- AC-3: Normalized passport number must be globally unique across all travelers in the platform. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0029`
- AC-4: Duplicate passport number registration returns HTTP 422 with traveler.passport_conflict without disclosing owner details. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0029`
- AC-5: Successfully added traveler appears in the customer's saved travelers list linked to their account_id. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0029`

**Sources:**
- `specs/domains/travelers.md:1-130`

**Status:** pending

## STORY-0037

**Epic:** EPIC-008 — Travelers
**Title:** Manage Saved Traveler Profiles

**As a** Authenticated Customer
**I want** to update and list my saved traveler profiles while preserving past order snapshots
**So that** my profile updates apply to future orders without altering historical order records

**Acceptance criteria:**
- AC-1: Listing travelers returns a paginated list belonging strictly to the requesting customer's account_id. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0031`
- AC-2: Updating a traveler profile updates future usage only; historical order snapshots retain original traveler data unchanged. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0031`
- AC-3: Updating a traveler profile enforces ownership; non-owners receive HTTP 404. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0031`
- AC-4: GetOwnedTravelerSnapshot query returns immutable traveler snapshot for checkout, returning traveler.not_found if unowned or missing. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0031`

**Sources:**
- `specs/domains/travelers.md:1-130`

**Status:** pending

## STORY-0038

**Epic:** EPIC-008 — Travelers
**Title:** Traveler Passport Vault and Profile Save Journey

**As a** Authenticated Customer
**I want** to save a traveler profile with validated, normalized passport and correct date chronology to my personal vault
**So that** I can select saved travelers in future order checkouts for fast, error-free ordering

**Acceptance criteria:**
- AC-1: Passport normalization strips all non-alphanumeric characters and spaces, converts to uppercase, and validates ^[A-Z0-9]{6,12}$, returning HTTP 422 traveler.invalid_passport_format on failure. · impact:`local` · seam:`integration` · scenario:`JOURNEY-0003`
- AC-2: Platform enforces global passport uniqueness: HTTP 422 traveler.passport_conflict returned without disclosing the owner account details. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0003`
- AC-3: Date chronology enforced: DOB < Issue Date < Expiry Date; violation returns HTTP 422 traveler.invalid_dates. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0003`
- AC-4: Saved traveler card appears immediately in the customer vault and is selectable in future order flows. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0003`
- AC-5: Updating a traveler (e.g. passport renewal) does NOT alter the frozen TravelerSnapshot of historical commercial orders. · impact:`cross-surface` · seam:`integration` · scenario:`JOURNEY-0003`

**Sources:**
- `specs/journeys/journey-03-traveler-profile-management.md:1-142`

**Status:** pending

## STORY-0039

**Epic:** EPIC-008 — Travelers
**Title:** Passport Normalization and Format Validation Determinism

**As a** System Validation Engine
**I want** to deterministically normalize and validate passport numbers against the ^[A-Z0-9]{6,12}$ rule
**So that** all passport number storage and uniqueness checks operate on canonical normalized form

**Acceptance criteria:**
- AC-1: PAS-01 to PAS-05: Normal valid passports (with/without leading/trailing whitespace, internal hyphens/spaces) normalize to uppercase alphanumeric and pass ^[A-Z0-9]{6,12}$ validation. · impact:`none` · seam:`unit`
- AC-2: PAS-06/PAS-07: Boundary lengths 6 and 12 characters are both valid. · impact:`none` · seam:`unit`
- AC-3: PAS-08/PAS-09: Lengths 5 (too short) and 13 (too long) return traveler.invalid_passport_format. · impact:`none` · seam:`unit`
- AC-4: PAS-10/PAS-11: Empty string and whitespace-only input return traveler.passport_required. · impact:`none` · seam:`unit`
- AC-5: PAS-12: Special characters like # are stripped, resulting length determines validity. · impact:`none` · seam:`unit`
- AC-6: UNI-01 to UNI-03: Same-account collision, different-account collision, and format-variation collision all return traveler.passport_conflict; UNI-04 (distinct values) succeeds. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0092`

**Sources:**
- `specs/test-vectors/passport-normalization-and-validation.md:1-77`

**Status:** pending

## STORY-0040

**Epic:** EPIC-008 — Travelers
**Title:** Traveler Date Sequence Validation

**As a** System Validation Engine
**I want** to enforce chronological ordering of DOB, passport issue date, and passport expiry date
**So that** all saved traveler profiles have valid and consistent date sequences

**Acceptance criteria:**
- AC-1: DAT-01: Valid sequence DOB < Issue < Expiry passes validation. · impact:`none` · seam:`unit`
- AC-2: DAT-02: Issue date before DOB returns traveler.invalid_dates. · impact:`none` · seam:`unit`
- AC-3: DAT-03: Expiry date before Issue date returns traveler.invalid_dates. · impact:`none` · seam:`unit`
- AC-4: DAT-04: Expiry date equal to Issue date returns traveler.invalid_dates (strict ordering required). · impact:`none` · seam:`unit`

**Sources:**
- `specs/test-vectors/passport-normalization-and-validation.md:1-77`

**Status:** pending