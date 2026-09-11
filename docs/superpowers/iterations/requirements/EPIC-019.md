# EPIC-019 — Content and Static Pages

**Summary:** Content and Static Pages
**Stories:** STORY-0080, STORY-0081
**Primary sources:** `specs/domains/content.md`
**Status:** 0/2 done

## STORY-0080

**Epic:** EPIC-019 — Content and Static Pages
**Title:** Static Content Authoring and Lifecycle

**As a** content administrator
**I want** to create and publish bilingual static pages with globally unique slugs
**So that** informational platform content can be safely authored in English and Arabic

**Acceptance criteria:**
- AC-1: CreateContentPage validates alphanumeric-hyphen slug, bilingual titles (3-150 chars), and bilingual bodies, creating draft page. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0087`
- AC-2: CreateContentPage rejects duplicate page slugs with HTTP 422 content.slug_exists. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0087`
- AC-3: PublishContentPage requires non-empty EN and AR bodies before setting status to published and setting published_at timestamp. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0087`
- AC-4: Content creation and publication require content.manage authorization. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0087`

**Sources:**
- `specs/domains/content.md:1-107`

**Status:** pending

## STORY-0081

**Epic:** EPIC-019 — Content and Static Pages
**Title:** Public Content Delivery and Internationalization

**As a** public website visitor
**I want** to view published static content pages in my selected language
**So that** I can read terms, guides, and policies in English or Arabic

**Acceptance criteria:**
- AC-1: GetPublicContentPage delivers published content in requested locale (EN or AR) with HTML XSS sanitization and automatic dir='rtl' for Arabic. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0088`
- AC-2: Draft content pages return HTTP 404 content.page_not_found to public guests and non-staff API clients. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0088`
- AC-3: Missing Arabic static UI strings fall back to English without throwing 500 errors. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0088`

**Sources:**
- `specs/domains/content.md:1-107`

**Status:** pending