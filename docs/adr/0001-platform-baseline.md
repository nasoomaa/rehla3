# ADR 0001: Platform Baseline and Modular Monolith Architecture

## Status
Accepted

## Context
Rehla requires a robust, maintainable, and verifiable foundation for Sudanese travel and platform services. The platform must enforce strict architectural boundaries across domains while avoiding premature microservice complexity.

## Decision
1. **Modular Monolith**: Build Rehla as a single Laravel 13.x host application on PHP 8.5 with 19 local Composer packages located under `packages/Rehla/<Package>`.
2. **Database**: Standardize strictly on PostgreSQL 18. SQLite is prohibited for integration, money, and concurrency testing. All integration test databases must be named with a `_testing` suffix.
3. **Boundaries**:
   - Packages communicate strictly via declared Public APIs: Actions, Queries, Contracts, and DTOs.
   - Mutable Eloquent Models and migrations are private to their owning package and never cross boundaries.
   - Presentation packages (`Web`, `Api`, `Admin`) must never write directly to business domain tables.
   - `Core` is completely independent and does not depend on any business package.
4. **Operations & Recovery**:
   - Single transactional database with PostgreSQL Outbox pattern for asynchronous event delivery.
   - RPO = 15 minutes, RTO = 4 hours. Point-in-time recovery for database and private blob storage.

## Consequences
- Clean separation of concerns with compile-time / static-analysis enforced package dependencies.
- Strict architecture tests prevent dependency leaks and model coupling.
- Test suites must run against real PostgreSQL instances.
