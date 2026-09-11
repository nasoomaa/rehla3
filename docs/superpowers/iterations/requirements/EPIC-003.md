# EPIC-003 — Customer REST API

**Summary:** Customer REST API
**Stories:** STORY-0008, STORY-0009, STORY-0010, STORY-0011, STORY-0012, STORY-0013, STORY-0014, STORY-0015, STORY-0016, STORY-0017, STORY-0018, STORY-0019, STORY-0020
**Primary sources:** `specs/contracts/customer-rest-api-v1.md`
**Status:** 0/13 done

## STORY-0008

**Epic:** EPIC-003 — Customer REST API
**Title:** Customer REST API General Conventions, Localization, and Error Standard

**As a** API Client
**I want** to send standard API headers and handle unified RFC 7807 problem+json error formats
**So that** consistent client integration, request context, and localized error responses work across surfaces

**Acceptance criteria:**
- AC-1: API accepts application/json Content-Type and Accept headers for standard endpoints, and multipart/form-data for /uploads. · impact:`local` · seam:`integration`
- AC-2: Protected API endpoints require Authorization: Bearer <token> header for access. · impact:`local` · seam:`integration`
- AC-3: Accept-Language header (en or ar) controls error message and content localization. · impact:`local` · seam:`integration`
- AC-4: Errors return standard RFC 7807 application/problem+json response containing type, title, status, code, message, errors, and trace_id. · impact:`local` · seam:`integration`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:18-37`

**Status:** pending

## STORY-0009

**Epic:** EPIC-003 — Customer REST API
**Title:** Customer Account Registration via REST API

**As a** Unauthenticated User
**I want** to register a new customer account via POST /api/v1/auth/register
**So that** I can access protected services and create a platform customer account

**Acceptance criteria:**
- AC-1: Successful registration returns HTTP 201 Created with created user object (id, name, email, locale, created_at) and Bearer token. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0008`
- AC-2: Registering with an already existing email returns HTTP 422 with code auth.email_exists. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0008`
- AC-3: Registering with a weak password returns HTTP 422 with code auth.weak_password. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0008`
- AC-4: Registration endpoint enforces strict rate limit of 5 requests per minute per IP. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0008`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:41-70`

**Status:** pending

## STORY-0010

**Epic:** EPIC-003 — Customer REST API
**Title:** Customer Login, Logout, and Profile Management

**As a** Customer
**I want** to authenticate via POST /api/v1/auth/login, view/update profile, and logout
**So that** I can maintain secure account access and manage personal account settings

**Acceptance criteria:**
- AC-1: Valid credentials on POST /api/v1/auth/login return HTTP 200 OK with user object and Bearer token. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0010`
- AC-2: Invalid login credentials return HTTP 401 with code auth.invalid_credentials. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0010`
- AC-3: Login attempts on suspended accounts return HTTP 403 with code auth.account_suspended. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0010`
- AC-4: Failed login endpoint enforces rate limit of 5 failed requests per 5 minutes per IP. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0010`
- AC-5: POST /api/v1/auth/logout with Bearer token revokes token and returns HTTP 204 No Content. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0010`
- AC-6: GET /api/v1/me returns authenticated user profile and PATCH /api/v1/me updates name and locale. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0010`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:72-85`

**Status:** pending

## STORY-0011

**Epic:** EPIC-003 — Customer REST API
**Title:** Browse Service Catalog and Retrieve Dynamic Application Forms

**As a** Public / Customer User
**I want** to browse active services, detailed service information, and dynamic application form schemas
**So that** I can inspect pricing, prerequisites, and form fields before ordering

**Acceptance criteria:**
- AC-1: GET /api/v1/services returns list of active services with price in minor units, formatted price, currency, duration, and image URL. · impact:`local` · seam:`integration`
- AC-2: GET /api/v1/services/{service_slug} returns service details, prerequisites array, requirements, price, and pre-filled WhatsApp deep-link metadata. · impact:`local` · seam:`integration`
- AC-3: GET /api/v1/services/{service_slug}/application-form returns service_id, form_version_id, schema_checksum, and field specifications. · impact:`local` · seam:`integration`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:88-141`

**Status:** pending

## STORY-0012

**Epic:** EPIC-003 — Customer REST API
**Title:** Traveler Vault Profile Management

**As a** Customer
**I want** to manage saved traveler profiles and enforce platform-wide passport uniqueness
**So that** I can store traveler details for reuse across visa orders without duplicating passport numbers

**Acceptance criteria:**
- AC-1: GET /api/v1/travelers returns paginated array of travelers belonging to calling account. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0012`
- AC-2: POST /api/v1/travelers creates traveler after stripping spaces/dashes, converting passport_number to uppercase, and validating regex ^[A-Z0-9]{6,12}$. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0012`
- AC-3: POST /api/v1/travelers returns HTTP 422 traveler.passport_conflict if passport number collides with any existing traveler on the platform. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0012`
- AC-4: PATCH /api/v1/travelers/{id} updates owned traveler profile (HTTP 200) or returns HTTP 404 Not Found if requested by non-owner. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0012`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:144-171`

**Status:** pending

## STORY-0013

**Epic:** EPIC-003 — Customer REST API
**Title:** Query Wallet Balance, Ledger Entries, and Platform Bank Accounts

**As a** Customer
**I want** to view current wallet balance, ledger transactions, and platform bank accounts
**So that** I can monitor available funds and obtain deposit account information for bank transfers

**Acceptance criteria:**
- AC-1: GET /api/v1/wallet returns current currency, balance_minor, formatted_balance, and status. · impact:`local` · seam:`integration`
- AC-2: GET /api/v1/wallet/entries returns paginated transaction ledger history. · impact:`local` · seam:`integration`
- AC-3: GET /api/v1/bank-accounts returns active platform bank accounts (bank name, account number, beneficiary name, logo URL). · impact:`local` · seam:`integration`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:175-194`

**Status:** pending

## STORY-0014

**Epic:** EPIC-003 — Customer REST API
**Title:** Wallet Top-Up Submission and Validation

**As a** Customer
**I want** to submit bank transfer top-up requests with receipt document and transaction reference
**So that** I can request manual wallet balance top-ups for administrative verification

**Acceptance criteria:**
- AC-1: POST /api/v1/top-ups with valid inputs creates top-up record with status under_review and returns HTTP 201 Created. · impact:`cross-surface` · seam:`integration`
- AC-2: Submitting top-up with amount_minor below 500,000 (5,000 SDG) returns HTTP 422 top_up.below_minimum. · impact:`local` · seam:`integration`
- AC-3: Submitting top-up with transaction_reference already used for target bank account returns HTTP 422 top_up.reference_used. · impact:`local` · seam:`integration`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:196-220`

**Status:** pending

## STORY-0015

**Epic:** EPIC-003 — Customer REST API
**Title:** Document Upload and Binary Streaming

**As a** Customer
**I want** to upload binary documents with validation and stream owned document content
**So that** I can store receipt and application documents safely and retrieve binary content securely

**Acceptance criteria:**
- AC-1: POST /api/v1/uploads accepts multipart/form-data, inspects magic bytes, validates size limits (PDF <= 20MB, JPEG/PNG <= 10MB), and returns document_id with status clean. · impact:`local` · seam:`integration`
- AC-2: Upload endpoint enforces rate limit of 10 requests per minute per account. · impact:`local` · seam:`integration`
- AC-3: GET /api/v1/documents/{id}/content streams binary content for owning customer with X-Content-Type-Options: nosniff header. · impact:`local` · seam:`integration`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:224-244`

**Status:** pending

## STORY-0016

**Epic:** EPIC-003 — Customer REST API
**Title:** Atomic Order Submission and Wallet Settlement

**As a** Customer
**I want** to submit a service application order with atomic debit of customer wallet balance and execution creation
**So that** I can execute instant order checkout with immediate execution tracking

**Acceptance criteria:**
- AC-1: POST /api/v1/order-submissions atomically debits customer wallet, creates order record, and creates execution with status received, returning HTTP 201 Created. · impact:`journey` · seam:`integration` · scenario:`SCENARIO-0013`
- AC-2: Order submission with accepted_price_minor unequal to database service price returns HTTP 409 service.price_changed. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0013`
- AC-3: Order submission when customer balance is lower than accepted price returns HTTP 422 wallet.insufficient_balance. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0013`
- AC-4: Order submission with outdated form_version_id returns HTTP 409 form.version_outdated. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0013`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:248-285`

**Status:** pending

## STORY-0017

**Epic:** EPIC-003 — Customer REST API
**Title:** Idempotent Order Submission Protocol

**As a** Customer / API Client
**I want** to retry order submissions safely using Idempotency-Key header
**So that** duplicate order debits are prevented during network timeouts and key payload mismatches trigger conflicts

**Acceptance criteria:**
- AC-1: Replaying identical POST /order-submissions payload with same Idempotency-Key returns HTTP 200 OK with original order outcome and zero additional debits. · impact:`journey` · seam:`integration` · scenario:`SCENARIO-0015`
- AC-2: Reusing Idempotency-Key with altered payload returns HTTP 409 Conflict with code order.idempotency_conflict. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0015`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:307-313`

**Status:** pending

## STORY-0018

**Epic:** EPIC-003 — Customer REST API
**Title:** Order Tracking and Customer Action Responses

**As a** Customer
**I want** to view order progress and submit responses/documents to execution action requests
**So that** I can track service execution and resolve requested action items to advance order processing

**Acceptance criteria:**
- AC-1: GET /api/v1/orders and GET /api/v1/orders/{order_ref} return order summary, frozen snapshots, and execution progress. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0017`
- AC-2: POST /api/v1/executions/{execution_id}/actions/{action_id}/responses accepts notes and clean document IDs, transitioning execution from action_required to action_received. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0017`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:287-293`

**Status:** pending

## STORY-0019

**Epic:** EPIC-003 — Customer REST API
**Title:** Notification Management

**As a** Customer
**I want** to view paginated notifications and mark individual notifications as read
**So that** I stay updated on order execution status and action items

**Acceptance criteria:**
- AC-1: GET /api/v1/notifications returns paginated list of notifications with unread count. · impact:`local` · seam:`integration`
- AC-2: POST /api/v1/notifications/{id}/read marks the notification as read. · impact:`local` · seam:`integration`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:299-303`

**Status:** pending

## STORY-0020

**Epic:** EPIC-003 — Customer REST API
**Title:** Rate Limiting Enforcement across Customer REST API

**As a** Customer / Public Client
**I want** to have per-IP and per-account rate limits enforced across API categories
**So that** API availability is protected and standard rate limiting headers are returned

**Acceptance criteria:**
- AC-1: Auth endpoints enforce 5 requests per minute per IP. · impact:`local` · seam:`integration`
- AC-2: Uploads and Order Submissions enforce 10 requests per minute per account. · impact:`local` · seam:`integration`
- AC-3: General Read APIs enforce 60 requests per minute per account or IP. · impact:`local` · seam:`integration`
- AC-4: Rate limited responses return headers X-RateLimit-Limit, X-RateLimit-Remaining, and Retry-After. · impact:`local` · seam:`integration`

**Sources:**
- `specs/contracts/customer-rest-api-v1.md:316-322`

**Status:** pending