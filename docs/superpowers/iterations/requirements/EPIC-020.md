# EPIC-020 — Service Discovery

**Summary:** Service Discovery
**Stories:** STORY-0082
**Primary sources:** `specs/journeys/journey-01-service-discovery-and-inquiry.md`
**Status:** 0/1 done

## STORY-0082

**Epic:** EPIC-020 — Service Discovery
**Title:** Browse Public Service Catalog and WhatsApp Inquiry

**As a** Guest or Customer
**I want** to browse published travel services, view their details, and initiate a WhatsApp inquiry without creating any orders or financial debits
**So that** I can evaluate services before committing to a purchase

**Acceptance criteria:**
- AC-1: Public storefront displays only services in active status with service names, marketing imagery, short descriptions, and starting prices formatted as SDG monetary strings (e.g. 25,000.00 SDG / ج.س). · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0001`
- AC-2: Service details page displays high-res marketing images, authoritative SDG price, estimated processing duration, informational requirements checklist, and two CTAs: 'Order Now' and 'Inquire via WhatsApp'. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0001`
- AC-3: WhatsApp inquiry button generates a deep-link URL to wa.me with pre-filled service context text including service name and price, opened with rel='noopener noreferrer'. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0001`
- AC-4: Clicking WhatsApp inquiry creates zero database state changes: no orders, no executions, no wallet debits, and no session creation. · impact:`local` · seam:`integration` · scenario:`JOURNEY-0001`
- AC-5: Switching language to Arabic re-renders service page with dir='rtl' layout and WhatsApp deep-link pre-fills Arabic message text. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0001`
- AC-6: Accessing a deactivated or draft service by slug returns HTTP 404 Not Found; no 'Order Now' or WhatsApp button is rendered. · impact:`local` · seam:`app-level` · scenario:`JOURNEY-0001`

**Sources:**
- `specs/journeys/journey-01-service-discovery-and-inquiry.md:1-114`

**Status:** pending