# EPIC-007 — Identity and Access Management

**Summary:** Identity and Access Management
**Stories:** STORY-0031, STORY-0032, STORY-0033, STORY-0034, STORY-0035
**Primary sources:** `specs/domains/identity-and-access.md`, `specs/journeys/journey-02-account-registration-and-profile.md`
**Status:** 0/5 done

## STORY-0031

**Epic:** EPIC-007 — Identity and Access Management
**Title:** Customer Account Registration

**As a** Prospective Customer
**I want** to register a new account with my full name, email, password, and preferred language
**So that** I can access the platform and manage my orders and wallet

**Acceptance criteria:**
- AC-1: Email must be normalized (lowercased, whitespace stripped) and unique across all active accounts. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0025`
- AC-2: Password must be at least 8 characters long with at least 1 uppercase letter, 1 lowercase letter, and 1 numeric character. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0025`
- AC-3: Successful registration returns HTTP 201 Created with account profile and initial Bearer token, setting account status to active. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0025`
- AC-4: Registration endpoint enforces strict IP-based rate limiting (max 5 requests per minute). · impact:`local` · seam:`integration` · scenario:`SCENARIO-0025`
- AC-5: Attempting to register with an existing active email returns HTTP 422 with auth.email_exists error. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0025`
- AC-6: Registration emits CustomerRegistered domain event and triggers wallet initialization. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0025`

**Sources:**
- `specs/domains/identity-and-access.md:1-140`

**Status:** pending

## STORY-0032

**Epic:** EPIC-007 — Identity and Access Management
**Title:** Customer Authentication

**As a** Registered Customer
**I want** to authenticate using my email and password
**So that** I can obtain a valid Bearer token for API and web access

**Acceptance criteria:**
- AC-1: Successful customer authentication returns HTTP 200 OK with Bearer token and user profile. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0027`
- AC-2: Authentication endpoint enforces rate limiting (max 5 failed attempts per 5 minutes). · impact:`local` · seam:`integration` · scenario:`SCENARIO-0027`
- AC-3: Credentials mismatch or suspended account returns HTTP 401 with auth.invalid_credentials or auth.account_suspended. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0027`
- AC-4: Email matching for authentication is case-insensitive. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0027`

**Sources:**
- `specs/domains/identity-and-access.md:1-140`

**Status:** pending

## STORY-0033

**Epic:** EPIC-007 — Identity and Access Management
**Title:** Staff Authentication and MFA Enforcement

**As a** Staff Member
**I want** to authenticate with email, password, and mandatory TOTP MFA when holding privileged abilities
**So that** I can access administrative features according to least privilege and security policies

**Acceptance criteria:**
- AC-1: Successful staff authentication redirects to administrative control plane overview. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0028`
- AC-2: Account locks out after 3 failed login attempts, returning HTTP 401 or HTTP 403 with auth.staff_access_denied. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0028`
- AC-3: Staff with topups.review, access.manage, or audit.view abilities cannot execute administrative operations without an active TOTP MFA session. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0028`

**Sources:**
- `specs/domains/identity-and-access.md:1-140`

**Status:** pending

## STORY-0034

**Epic:** EPIC-007 — Identity and Access Management
**Title:** Session and Token Management

**As a** Authenticated Customer
**I want** to manage my Bearer tokens and profile information, while ensuring isolated session guards
**So that** my account remains secure across interfaces and invalid sessions are rejected

**Acceptance criteria:**
- AC-1: Token revocation endpoint returns HTTP 204 No Content and invalidates subsequent requests using that token with HTTP 401. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0027`
- AC-2: Profile update allows changing full name (3-100 chars) and preferred language (en/ar), while prohibiting email changes. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0027`
- AC-3: Customer and Admin sessions use separate cookies and distinct session stores. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0027`
- AC-4: Suspending an account invalidates all active sessions on the next request. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0027`
- AC-5: Accounts with financial ledger entries, orders, or top-ups cannot be permanently deleted and may only be suspended. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0027`

**Sources:**
- `specs/domains/identity-and-access.md:1-140`

**Status:** pending

## STORY-0035

**Epic:** EPIC-007 — Identity and Access Management
**Title:** Customer Account Registration and Wallet Provisioning Journey

**As a** New User
**I want** to register an account, provision an initial zero-balance SDG wallet, receive a welcome notification, and access my customer dashboard
**So that** I can authenticate and begin ordering services

**Acceptance criteria:**
- AC-1: Registration normalizes email to lowercase, verifies RFC 5322 syntax, and checks for collision, rejecting duplicate emails with HTTP 422 auth.email_exists before creating any DB record. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0002`
- AC-2: Within the same atomic transaction: user record is created in active status, wallet is provisioned with SDG currency and balance_minor=0 in active status, initial welcome in-app notification is inserted, and CustomerRegistered outbox message is enqueued. · impact:`journey` · seam:`integration` · scenario:`JOURNEY-0002`
- AC-3: On successful registration, customer is redirected to /account dashboard displaying greeting with their full name and wallet balance '0.00 SDG'. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0002`
- AC-4: Password must be Argon2id/bcrypt hashed; plain-text passwords are never persisted. · impact:`local` · seam:`integration` · scenario:`JOURNEY-0002`
- AC-5: Weak password (not meeting complexity rules) returns HTTP 422 with message 'Password must be at least 8 characters and include uppercase, lowercase, and numbers.' · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0002`
- AC-6: Suspended account login attempt returns HTTP 403 Forbidden with code auth.account_suspended. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0002`

**Sources:**
- `specs/journeys/journey-02-account-registration-and-profile.md:1-140`

**Status:** pending