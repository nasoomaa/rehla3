# EPIC-015 — Documents

**Summary:** Documents
**Stories:** STORY-0070, STORY-0071, STORY-0072, STORY-0073
**Primary sources:** `specs/domains/documents.md`
**Status:** 0/4 done

## STORY-0070

**Epic:** EPIC-015 — Documents
**Title:** Document Upload and Format Quota Enforcement

**As a** Authenticated Customer
**I want** to upload documents to private storage
**So that** they can be scanned and used for applications or top-ups

**Acceptance criteria:**
- AC-1: Store all customer-uploaded documents on private disk in pending_scan status and schedule asynchronous virus and integrity scanning. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0068`
- AC-2: Enforce format whitelist (.pdf, .jpg, .jpeg, .png) and size quotas (<= 10 MiB for images, <= 20 MiB for PDFs), returning HTTP 413 for oversized files and HTTP 422 document.unsupported_type for prohibited file extensions. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0068`
- AC-3: Inspect file magic bytes (%PDF for PDF, 0xFF 0xD8 0xFF for JPEG, 0x89 0x50 0x4E 0x47 for PNG) to detect tampered file extensions. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0068`
- AC-4: Rate-limit document uploads to a maximum of 10 uploads per minute per account. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0068`

**Sources:**
- `specs/domains/documents.md:1-148`

**Status:** pending

## STORY-0071

**Epic:** EPIC-015 — Documents
**Title:** Automated Document Scanning and Security Verification

**As a** System Malware Engine
**I want** to scan uploaded documents for integrity and viruses
**So that** only verified clean documents can be linked to entity transactions

**Acceptance criteria:**
- AC-1: Inspect magic bytes, verify image decodability, execute malware scan, and update status to clean or rejected. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0071`
- AC-2: Restrict document attachment in parent transactions exclusively to clean status documents belonging to the calling account. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0071`
- AC-3: Perform locking with SELECT FOR UPDATE during checkout attachment transaction to prevent race conditions. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0071`
- AC-4: Abort transaction and return HTTP 422 document.invalid_attachment if any document is not clean, already attached, or belongs to another account. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0071`

**Sources:**
- `specs/domains/documents.md:1-148`

**Status:** pending

## STORY-0072

**Epic:** EPIC-015 — Documents
**Title:** Private Document Streaming and Authorized Access

**As a** Authorized User
**I want** to stream or download private documents securely
**So that** document contents are accessible to authorized parties without public exposure

**Acceptance criteria:**
- AC-1: Prohibit public access and require explicit ownership check or documents.view_sensitive staff permission, returning HTTP 403 or 404 for unauthorized requests. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0073`
- AC-2: Serve downloads via short-lived signed URLs (<= 15 min) or authorized proxy streaming with security headers (Content-Disposition, X-Content-Type-Options: nosniff, Cache-Control: private, no-store). · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0073`
- AC-3: Record staff access to private customer documents in the audit log. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0073`

**Sources:**
- `specs/domains/documents.md:1-148`

**Status:** pending

## STORY-0073

**Epic:** EPIC-015 — Documents
**Title:** Document Lifecycle Retention and Automated Pruning

**As a** System Scheduled Pruning Job
**I want** to enforce lifecycle retention policies on unattached and rejected documents
**So that** storage space is managed while maintaining mandatory audit records

**Acceptance criteria:**
- AC-1: Purge orphaned clean or pending_scan uploads that remain unattached for > 24 hours by deleting storage blobs and marking records expunged. · impact:`local` · seam:`process-level` · scenario:`SCENARIO-0075`
- AC-2: Retain rejected or infected uploads in quarantine for 30 days before permanent erasure. · impact:`local` · seam:`process-level` · scenario:`SCENARIO-0075`
- AC-3: Permanently retain documents in attached status, ensuring the pruning job NEVER deletes attached documents. · impact:`local` · seam:`process-level` · scenario:`SCENARIO-0075`

**Sources:**
- `specs/domains/documents.md:1-148`

**Status:** pending