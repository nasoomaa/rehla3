# EPIC-009 — Service Catalog

**Summary:** Service Catalog
**Stories:** STORY-0041, STORY-0042
**Primary sources:** `specs/domains/service-catalog.md`
**Status:** 0/2 done

## STORY-0041

**Epic:** EPIC-009 — Service Catalog
**Title:** Create and Publish Catalog Services

**As a** Admin User
**I want** to create catalog services and publish them when a form schema is linked
**So that** customers can view and select active services on the storefront

**Acceptance criteria:**
- AC-1: Service creation requires services.manage ability, name (3-100 chars), unique URL-safe slug, non-empty requirements list, and minimum price of 100 SDG (amount_minor >= 10000). · impact:`local` · seam:`integration` · scenario:`SCENARIO-0032`
- AC-2: Creating service with duplicate slug or price < 100 SDG returns HTTP 422. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0032`
- AC-3: Publishing a draft or inactive service requires an active published form schema linked and transitions service state to Active. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0032`
- AC-4: Publishing a service without a valid published form version fails with service.missing_form_version. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0032`

**Sources:**
- `specs/domains/service-catalog.md:1-115`

**Status:** pending

## STORY-0042

**Epic:** EPIC-009 — Service Catalog
**Title:** Manage Service Prices and Deactivation

**As a** Admin User
**I want** to update active service prices and deactivate unavailable services
**So that** storefront pricing stays current while preserving historical price logs and existing orders

**Acceptance criteria:**
- AC-1: Updating service price closes existing price history row, inserts a new active price row, emits ServicePriceUpdated event, and updates storefront price immediately. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0034`
- AC-2: Historical price records are immutable and can never be updated or deleted. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0034`
- AC-3: Deactivating a service hides it from public catalog or displays Currently Unavailable, and causes new order submissions to fail with HTTP 422 service.unavailable. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0034`
- AC-4: Deactivation prevents new orders but does not invalidate historical orders or in-flight executions. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0034`
- AC-5: Price change during application form submission returns HTTP 409 Conflict with service.price_changed and old/new price details. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0034`

**Sources:**
- `specs/domains/service-catalog.md:1-115`

**Status:** pending