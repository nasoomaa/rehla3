# Rehla Reporting Package

Part of the Rehla Modular Monolith platform.

## Overview
The `Reporting` package provides read-only analytical queries and metrics for the Rehla platform. It strictly enforces read-only access to source packages without performing any database mutations or side effects.

## Twelve Phase 1 Product Metrics
1. **registered_users**: Count of user accounts registered in period `[fromUtc, toUtc)`.
2. **saved_travelers**: Count of saved travelers added in period.
3. **total_orders / order_volume_minor / gross_revenue_sdg**: Total count of paid orders and sum of order amounts in SDG minor units.
4. **top_up_volume_minor**: Sum of approved top-up amounts in SDG minor units.
5. **top_up_completion_rate**: Percentage of submitted top-ups decided in period `(approved + rejected) / submitted * 100`.
6. **top_up_approval_ratio**: Ratio of approved top-ups to rejected top-ups.
7. **top_up_rejection_rate**: Percentage of submitted top-ups rejected in period.
8. **average_review_seconds**: Average seconds from top-up submission to decision.
9. **orders_by_service / service_popularity_breakdown**: Orders and revenue grouped by service code and service snapshot name.
10. **average_fulfillment_seconds**: Average seconds from execution creation to completion.
11. **customer_action_volume / action_request_rate**: Volume of customer action requests and percentage of executions requiring customer actions.
12. **completed_order_percentage**: Percentage of created executions that reached completed status.
13. **traveler_reuse_rate**: Percentage of travelers used in more than one order.
14. **repeat_customer_rate**: Percentage of customer accounts with more than one order.
15. **bank_topup_channel_share**: Top-up volume grouped by company bank account.
16. **system_outbox_lag_seconds**: Maximum seconds between outbox event creation and delivery.

## Read-Only Guarantee
- Zero mutable Eloquent models.
- Queries execute exclusively read operations (`SELECT`).
- Views named with prefix `reporting_*`.
