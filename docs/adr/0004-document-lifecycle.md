# ADR 0004: Document Storage Lifecycle, Privacy, and Passport Normalization

## Status
Accepted

## Context
Travel documents (passports, national IDs, visas) and bank transfer receipts contain sensitive PII and financial proof. They require strict file validation, private storage isolation, defined retention policies, and deterministic identity normalization.

## Decision
1. **File Type & Size Validation**:
   - Allowed MIME types / extensions: PDF, JPEG, and PNG only.
   - Max file size: 10 MiB for images (JPEG, PNG); 20 MiB for PDF documents.
   - Validation must verify magic bytes, image/PDF decodability, and execute virus/malware inspection before acceptance.
2. **Private Storage & Access Control**:
   - All traveler documents and payment receipts are private by default in private storage disks.
   - Permanent public URLs are strictly forbidden. Access is provided exclusively through short-lived signed URLs or authenticated proxy downloads.
   - Access is restricted to the owning account holder and authorized operations personnel.
3. **Retention Policy**:
   - Temporary unattached uploads are purged after 24 hours.
   - Rejected top-up receipts or rejected documents are retained for 30 days for audit dispute resolution before cleanup.
   - Documents attached to completed orders follow the statutory travel record retention schedule.
4. **Passport Normalization & Uniqueness**:
   - Passport numbers are normalized by converting to uppercase and stripping all whitespace and hyphens (`preg_replace('/[^A-Z0-9]/', '', strtoupper($passport))`).
   - Global uniqueness is enforced at the database level on the normalized passport value.

## Consequences
- Prevents malicious file uploads and unauthorized document leaks.
- Avoids duplicate traveler records resulting from formatting variations.
