# EPIC-014 — Application Forms

**Summary:** Application Forms
**Stories:** STORY-0066, STORY-0067, STORY-0068, STORY-0069
**Primary sources:** `specs/domains/application-forms.md`, `specs/test-vectors/form-schema-validation-and-evaluation.md`
**Status:** 0/4 done

## STORY-0066

**Epic:** EPIC-014 — Application Forms
**Title:** Form Draft Creation and Editing

**As a** Service Administrator
**I want** to create and update form drafts for a service
**So that** form schemas can be built and validated before publishing

**Acceptance criteria:**
- AC-1: Limit active drafts to at most one per service at a time for users with forms.draft ability. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0063`
- AC-2: Validate field types against 11 supported types, require unique field keys within the form, and require dropdown/radio fields to specify at least two options. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0063`
- AC-3: Keep draft modifications completely invisible to customers. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0063`
- AC-4: Return HTTP 422 with field-level details on invalid schema update. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0063`

**Sources:**
- `specs/domains/application-forms.md:1-132`

**Status:** pending

## STORY-0067

**Epic:** EPIC-014 — Application Forms
**Title:** Publishing Immutable Form Versions

**As a** Service Administrator
**I want** to publish a valid form draft
**So that** customers can fill out the updated authoritative form schema

**Acceptance criteria:**
- AC-1: Increment version number strictly monotonically (current + 1), calculate and lock SHA-256 cryptographic checksum of normalized JSON schema, mark version active, and archive draft. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0065`
- AC-2: Render published form version permanently immutable (cannot be edited, overwritten, re-ordered, or deleted). · impact:`local` · seam:`integration` · scenario:`SCENARIO-0065`
- AC-3: Immediately return new version on GET /api/v1/services/{service}/application-form, emit FormVersionPublished event, and log Audit entry. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0065`

**Sources:**
- `specs/domains/application-forms.md:1-132`

**Status:** pending

## STORY-0068

**Epic:** EPIC-014 — Application Forms
**Title:** Form Submission Validation and Historical Preservation

**As a** System Checkout Engine
**I want** to validate customer form answers against published schema and freeze responses with version reference
**So that** submitted data integrity is guaranteed without modifying historical order forms

**Acceptance criteria:**
- AC-1: Freeze customer responses as JSON alongside exact form_version_id, preserving historical orders intact even when form schema is subsequently modified. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0066`
- AC-2: Reject missing required fields, unrecognized phantom field keys, and field type mismatches during submission validation. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0066`
- AC-3: Validate attached document IDs (ensure documents exist, are clean, and belong to current customer), returning HTTP 422 form.invalid_document_attachment on failure. · impact:`cross-surface` · seam:`integration` · scenario:`SCENARIO-0066`
- AC-4: Halt checkout and instruct user to refresh with HTTP 409 form.version_outdated when customer submits answers against a stale form version. · impact:`cross-surface` · seam:`app-level` · scenario:`SCENARIO-0066`

**Sources:**
- `specs/domains/application-forms.md:1-132`

**Status:** pending

## STORY-0069

**Epic:** EPIC-014 — Application Forms
**Title:** Application Form Schema Validation for All 11 Field Types

**As a** System Form Validation Engine
**I want** to validate customer form submissions against published schema for all 11 field types with correct required/optional, type, bounds, option, and document ownership checks
**So that** only valid data is captured and phantom fields or type violations are blocked at submission

**Acceptance criteria:**
- AC-1: FRM-01/FRM-04: short_text and long_text with valid lengths pass validation. · impact:`none` · seam:`unit`
- AC-2: FRM-02: short_text below min length returns form.text_too_short. · impact:`none` · seam:`unit`
- AC-3: FRM-03: Optional field omitted returns valid. · impact:`none` · seam:`unit`
- AC-4: FRM-05/FRM-06: Valid RFC 5322 email passes; invalid email format returns form.invalid_email_format. · impact:`none` · seam:`unit`
- AC-5: FRM-07/FRM-08: Valid E.164 phone passes; invalid phone format returns form.invalid_phone_format. · impact:`none` · seam:`unit`
- AC-6: FRM-09/FRM-10/FRM-11: Number within bounds passes; string input returns form.numeric_expected; out-of-bounds returns form.number_out_of_bounds. · impact:`none` · seam:`unit`
- AC-7: FRM-12/FRM-13: Valid ISO 8601 date passes; non-ISO format returns form.invalid_date_format. · impact:`none` · seam:`unit`
- AC-8: FRM-14 to FRM-17: Dropdown and radio fields accept valid options; undeclared option values return form.invalid_option_selected. · impact:`none` · seam:`unit`
- AC-9: FRM-18/FRM-19: Mandatory checkbox agreement accepts true; false returns form.mandatory_agreement_required. · impact:`none` · seam:`unit`
- AC-10: FRM-20 to FRM-23: file_upload/image_upload require clean document belonging to caller; quarantined returns form.document_not_clean; other-user document returns form.document_ownership_violation. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0099`
- AC-11: FRM-24: Undeclared (phantom) field in payload returns form.undeclared_field_rejected. · impact:`none` · seam:`unit`
- AC-12: CHK-01/CHK-02: Schema checksum matching passes; tampering detected by mismatch returns integrity violation alert. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0099`

**Sources:**
- `specs/test-vectors/form-schema-validation-and-evaluation.md:1-57`

**Status:** pending