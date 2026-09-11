# ADR 0003: Authentication, Session Lifecycle, and Mandatory MFA

## Status
Accepted

## Context
Rehla serves customers and back-office operations through web portals, administrative dashboards, and REST API endpoints. Financial approvals (such as bank deposit verification) and privilege delegation require defense-in-depth access controls.

## Decision
1. **Authentication Channels**:
   - Web Customer Portal and Back-Office Admin operate on stateful secure HTTP sessions with CSRF protection.
   - REST API endpoints (`/api/v1`) authenticate requests via Laravel Sanctum Personal Access Tokens (Bearer tokens).
2. **Mandatory Multi-Factor Authentication (MFA)**:
   - TOTP (Time-based One-Time Password) MFA is mandatory for all staff members holding sensitive administrative or financial capabilities, specifically:
     - Approving or rejecting bank top-up requests (`topups.approve`, `topups.reject`).
     - Managing administrative roles and permission grants (`roles.manage`).
     - Inspecting immutable system audit trails (`audit.view`).
   - Sensitive actions without verified MFA challenge will be rejected with HTTP 403 Forbidden.
3. **Session Invalidation**:
   - Password changes or security events immediately terminate all active sessions and revoke Sanctum tokens.

## Consequences
- Protects financial ledger operations from compromised credentials.
- Clear separation between stateful browser sessions and stateless API tokens.
