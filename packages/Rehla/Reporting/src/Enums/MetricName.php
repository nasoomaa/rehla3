<?php

declare(strict_types=1);

namespace Rehla\Reporting\Enums;

enum MetricName: string
{
    case RegisteredUsers = 'registered_users';
    case SavedTravelers = 'saved_travelers';
    case TotalOrders = 'total_orders';
    case GrossRevenueSdg = 'gross_revenue_sdg';
    case OrderVolumeMinor = 'order_volume_minor';
    case TopUpVolumeMinor = 'topup_volume_minor';
    case TopUpCompletionRate = 'top_up_completion_rate';
    case AverageReviewSeconds = 'average_review_seconds';
    case TopUpApprovalRatio = 'top_up_approval_ratio';
    case TopUpRejectionRate = 'top_up_rejection_rate';
    case OrdersByService = 'orders_by_service';
    case ServicePopularityBreakdown = 'service_popularity_breakdown';
    case AverageFulfillmentSeconds = 'average_fulfillment_seconds';
    case OrderFulfillmentTimeAvgHours = 'order_fulfillment_time_avg_hours';
    case CustomerActionVolume = 'customer_action_volume';
    case ActionRequestRate = 'action_request_rate';
    case CompletedOrderPercentage = 'completed_order_percentage';
    case TravelerReuseRate = 'traveler_reuse_rate';
    case RepeatCustomerRate = 'repeat_customer_rate';
    case ActiveCustomersCount = 'active_customers_count';
    case BankTopUpChannelShare = 'bank_topup_channel_share';
    case SystemOutboxLagSeconds = 'system_outbox_lag_seconds';
}
