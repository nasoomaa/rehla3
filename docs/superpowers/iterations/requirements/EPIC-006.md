# EPIC-006 — External Integrations

**Summary:** External Integrations
**Stories:** STORY-0029, STORY-0030
**Primary sources:** `specs/contracts/whatsapp-inquiry-contract.md`
**Status:** 0/2 done

## STORY-0029

**Epic:** EPIC-006 — External Integrations
**Title:** WhatsApp Inquiry Zero-Side-Effect External Handoff

**As a** unauthenticated or authenticated customer
**I want** to click the WhatsApp inquiry button and contact support without creating orders or mutating platform state
**So that** I can inquire about a service safely without any financial or order side effects

**Acceptance criteria:**
- AC-1: Clicking the 'Inquire via WhatsApp' button must not create orders, debit wallets, hold funds, create execution records, or draft forms, operating with zero database side-effects. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0024`
- AC-2: WhatsApp inquiry action must be accessible without requiring customer login or authentication. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0024`

**Sources:**
- `specs/contracts/whatsapp-inquiry-contract.md:16-26`

**Status:** pending

## STORY-0030

**Epic:** EPIC-006 — External Integrations
**Title:** WhatsApp Deep-Link Formatting and Localization

**As a** customer viewing a service page
**I want** a localized WhatsApp deep-link containing the authoritative service price that opens safely in a new tab
**So that** I can contact support with pre-filled context about the service I'm interested in

**Acceptance criteria:**
- AC-1: The generated link must follow format https://wa.me/{configured_support_phone_e164}?text={url_encoded_inquiry_message} with E.164 phone number and URL-encoded localized pre-filled text. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0024`
- AC-2: The pre-filled text must support English and Arabic localized templates containing service name and formatted price. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0024`
- AC-3: The HTML anchor tag must include target="_blank" rel="noopener noreferrer" and dynamically incorporate the authoritative price at render time. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0024`

**Sources:**
- `specs/contracts/whatsapp-inquiry-contract.md:30-70`

**Status:** pending