# EPIC-018 — Reporting and Analytics

**Summary:** Reporting and Analytics
**Stories:** STORY-0078, STORY-0079
**Primary sources:** `specs/domains/reporting.md`, `specs/test-vectors/reporting-metrics-calculations.md`
**Status:** 0/2 done

## STORY-0078

**Epic:** EPIC-018 — Reporting and Analytics
**Title:** Platform Analytics and Performance Reporting

**As a** platform administrator
**I want** to query aggregated platform metrics and performance reports across specified cohort periods
**So that** I can evaluate system KPIs, top-up turnaround times, fulfillment durations, and customer retention

**Acceptance criteria:**
- AC-1: GetPlatformOverviewMetrics returns aggregated KPIs M01-M12 evaluated strictly in Africa/Khartoum timezone with division-by-zero safety returning 0 or 0.00%. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0085`
- AC-2: GetTopUpPerformanceReport and GetFulfillmentPerformanceReport return throughput, turnaround, completion rates, and backlog status metrics. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0085`
- AC-3: Reporting queries are strictly read-only and historical metrics for closed past periods remain constant and reproducible. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0085`
- AC-4: Historical order metric groupings M07 preserve frozen service names from historical snapshots even if service is subsequently renamed. · impact:`local` · seam:`integration` · scenario:`SCENARIO-0085`
- AC-5: Reporting queries on launch day with zero activity return 0 or 0.00% without raising errors. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0085`
- AC-6: Requests without admin.overview.view return HTTP 403 reporting.access_denied, and invalid date ranges return HTTP 422 reporting.invalid_date_range. · impact:`local` · seam:`app-level` · scenario:`SCENARIO-0085`

**Sources:**
- `specs/domains/reporting.md:1-116`

**Status:** pending

## STORY-0079

**Epic:** EPIC-018 — Reporting and Analytics
**Title:** Reporting Metrics Calculation Accuracy and Division-by-Zero Safety

**As a** System Analytical Engine
**I want** to compute all 12 core metrics from benchmark fixtures with correct formulas, timezone boundaries, and zero-denominator safety
**So that** analytical reports are deterministic, reproducible, and safe for launch-day zero-activity scenarios

**Acceptance criteria:**
- AC-1: M01: Registered users count = 3 for benchmark cohort. · impact:`none` · seam:`unit`
- AC-2: M02: Saved travelers count = 4 for benchmark cohort. · impact:`none` · seam:`unit`
- AC-3: M03-A/M03-B: Paid order count = 3; gross value = '90,000.00 SDG' (9,000,000 minor). · impact:`none` · seam:`unit`
- AC-4: M04: Top-up completion rate = '75.00%' (3 decided / 4 submitted). · impact:`none` · seam:`unit`
- AC-5: M05: Average top-up turnaround = '40.00 minutes' ((30+60+30)/3). · impact:`none` · seam:`unit`
- AC-6: M06: Transfer approval ratio = '66.67%' (2 approved / 3 decided). · impact:`none` · seam:`unit`
- AC-7: M07: Orders breakdown groups correctly: UAE Visa 2 orders (50,000 SDG), Saudi Visa 1 order (40,000 SDG). · impact:`none` · seam:`unit`
- AC-8: M08: Fulfillment duration = '7.00 hours' ((8.0+6.0)/2 completed executions). · impact:`none` · seam:`unit`
- AC-9: M09: Customer action volume = 1 execution entered action_required. · impact:`none` · seam:`unit`
- AC-10: M10: Completed orders rate = '66.67%' (2 completed / 3 total). · impact:`none` · seam:`unit`
- AC-11: M11: Traveler reuse rate = '50.00%' (1 traveler with >1 order / 2 used travelers). · impact:`none` · seam:`unit`
- AC-12: M12: Customer retention rate = '50.00%' (1 customer with >1 order / 2 buyers). · impact:`none` · seam:`unit`
- AC-13: ZER-01 to ZER-07: All metrics with zero denominator return '0.00%' or '0.00' without exceptions. · impact:`none` · seam:`unit`

**Sources:**
- `specs/test-vectors/reporting-metrics-calculations.md:1-87`

**Status:** pending