# EPIC-005 — Document Storage

**Summary:** Document Storage
**Stories:** STORY-0025, STORY-0026, STORY-0027, STORY-0028
**Primary sources:** `specs/contracts/storage-and-document-pipeline.md`
**Status:** 0/4 done

## STORY-0025

**Epic:** EPIC-005 — Document Storage
**Title:** Storage Disk Partitioning and Access Control

**As a** Document domain
**I want** to segregate files into private and public disks
**So that** sensitive documents cannot be accessed via direct URLs while public media is served directly via CDN

**Acceptance criteria:**
- AC-1: Private disk documents (passports, IDs, receipts, attachments, visas) must reject direct URL access and require authorized streaming or signed temporary URLs with a max lifetime of 15 minutes. · impact:`cross-surface` · seam:`integration`
- AC-2: Public disk media must allow direct CDN URL delivery with appropriate cache headers. · impact:`local` · seam:`integration`

**Sources:**
- `specs/contracts/storage-and-document-pipeline.md:21-26`

**Status:** pending

## STORY-0026

**Epic:** EPIC-005 — Document Storage
**Title:** Document Validation and Sanitization Pipeline

**As a** storage pipeline
**I want** to validate magic bytes, strip EXIF metadata from images, enforce file size quotas, and scan for malware
**So that** only legitimate clean files are stored and malicious or corrupt files are rejected

**Acceptance criteria:**
- AC-1: Uploaded files must adhere to classification size limits (10/20 MiB for scans/receipts/photos/docs, 5 MiB for service media), returning HTTP 413 document.file_too_large if quota is exceeded. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0022`
- AC-2: The system must inspect magic bytes (PDF: %PDF-, JPEG: 0xFF 0xD8 0xFF, PNG: 0x89 0x50 0x4E 0x47...), returning HTTP 422 document.verification_failed on mismatch or image decompression failure. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0022`
- AC-3: Image streams must have EXIF metadata (GPS/camera data) stripped upon processing, and infected files matching ClamAV signatures must be marked rejected and quarantined. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0022`

**Sources:**
- `specs/contracts/storage-and-document-pipeline.md:30-64`

**Status:** pending

## STORY-0027

**Epic:** EPIC-005 — Document Storage
**Title:** Document Retention and Pruning Lifecycle

**As a** document lifecycle manager
**I want** to prune unattached orphan documents after 24 hours, expunge quarantined rejected files after 30 days, and preserve attached commercial documents permanently
**So that** storage is not wasted on orphan uploads while commercial records are preserved indefinitely

**Acceptance criteria:**
- AC-1: Unattached documents in clean or pending_scan state older than 24 hours must be pruned by the hourly scheduler. · impact:`local` · seam:`integration`
- AC-2: Rejected files in quarantine must be retained for 30 days before being permanently expunged. · impact:`local` · seam:`integration`
- AC-3: Documents attached to commercial orders or top-ups must be protected from orphan pruning and remain bound to parent commercial entities permanently. · impact:`cross-surface` · seam:`integration`

**Sources:**
- `specs/contracts/storage-and-document-pipeline.md:68-76`

**Status:** pending

## STORY-0028

**Epic:** EPIC-005 — Document Storage
**Title:** Authorized Private Document Streaming and Security Headers

**As a** authorized consumer
**I want** to stream private documents securely with strict security headers
**So that** unauthorized cross-account or unprivileged staff access is prevented

**Acceptance criteria:**
- AC-1: Authorized streaming requests must include security headers (Content-Disposition, Content-Security-Policy: default-src 'none', X-Content-Type-Options: nosniff, Cache-Control: private, no-cache, no-store, must-revalidate). · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0023`
- AC-2: Unauthorized document access must return HTTP 404 for cross-account requests and HTTP 403 for staff missing the documents.view_sensitive permission. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0023`

**Sources:**
- `specs/contracts/storage-and-document-pipeline.md:80-106`

**Status:** pending