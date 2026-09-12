<?php

declare(strict_types=1);

namespace Rehla\Reporting\Data;

final readonly class ProductMetrics
{
    /**
     * @param  array<int, array{service_id: string, name_en: string, name_ar: string, order_count: int, volume_minor: int}>  $ordersByService
     * @param  array<int, array{service_id: string, name_en: string, name_ar: string, order_count: int, volume_minor: int}>  $servicePopularityBreakdown
     * @param  array<int, array{bank_account_id: string, bank_name_en: string, bank_name_ar: string, topup_count: int, volume_minor: int}>  $bankTopUpChannelShare
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public MetricFilter $filter,
        public int $registeredUsers,
        public int $savedTravelers,
        public int $orderVolumeMinor,
        public int $totalOrders,
        public int $grossRevenueSdg,
        public int $topUpVolumeMinor,
        public ?float $topUpCompletionRate,
        public ?float $averageReviewSeconds,
        public ?float $topUpApprovalRatio,
        public ?float $topUpRejectionRate,
        public array $ordersByService,
        public array $servicePopularityBreakdown,
        public ?float $averageFulfillmentSeconds,
        public ?float $orderFulfillmentTimeAvgHours,
        public int $customerActionVolume,
        public ?float $actionRequestRate,
        public ?float $completedOrderPercentage,
        public ?float $travelerReuseRate,
        public ?float $repeatCustomerRate,
        public int $activeCustomersCount,
        public array $bankTopUpChannelShare,
        public ?float $systemOutboxLagSeconds,
        public array $extra = [],
    ) {}
}
