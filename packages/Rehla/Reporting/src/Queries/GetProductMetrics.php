<?php

declare(strict_types=1);

namespace Rehla\Reporting\Queries;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Rehla\Reporting\Data\MetricFilter;
use Rehla\Reporting\Data\ProductMetrics;

final class GetProductMetrics
{
    public function handle(MetricFilter $filter): ProductMetrics
    {
        $from = $filter->fromUtc->toDateTimeString();
        $to = $filter->toUtc->toDateTimeString();

        // 1. Registered Users
        $registeredUsers = (int) DB::table('users')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->count();

        // 2. Saved Travelers
        $savedTravelers = (int) DB::table('travelers')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->count();

        // 3. Orders and Volume
        $orderAgg = DB::table('orders')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->where('financial_status', 'paid')
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(amount_paid_minor), 0) as total_volume')
            ->first();

        $totalOrders = (int) ($orderAgg->total_orders ?? 0);
        $orderVolumeMinor = (int) ($orderAgg->total_volume ?? 0);
        $grossRevenueSdg = $orderVolumeMinor;

        // 4. Top-ups metrics
        $topUps = DB::table('topup_requests')
            ->where('submitted_at', '>=', $from)
            ->where('submitted_at', '<', $to)
            ->get();

        $submittedTopUps = $topUps->count();
        $approvedTopUps = $topUps->where('status', 'approved');
        $rejectedTopUps = $topUps->where('status', 'rejected');
        $approvedCount = $approvedTopUps->count();
        $rejectedCount = $rejectedTopUps->count();
        $decidedCount = $approvedCount + $rejectedCount;

        $topUpVolumeMinor = (int) $approvedTopUps->sum('amount_minor');

        $topUpCompletionRate = $submittedTopUps > 0
            ? round(($decidedCount / $submittedTopUps) * 100, 2)
            : null;

        $topUpRejectionRate = $submittedTopUps > 0
            ? round(($rejectedCount / $submittedTopUps) * 100, 2)
            : null;

        $topUpApprovalRatio = $rejectedCount > 0
            ? round($approvedCount / $rejectedCount, 2)
            : null;

        $reviewDurations = [];
        foreach ($topUps as $t) {
            if (in_array($t->status, ['approved', 'rejected'], true) && $t->decided_at !== null && $t->submitted_at !== null) {
                $sub = CarbonImmutable::parse($t->submitted_at);
                $dec = CarbonImmutable::parse($t->decided_at);
                $reviewDurations[] = abs((float) $dec->diffInSeconds($sub));
            }
        }
        $averageReviewSeconds = count($reviewDurations) > 0
            ? round(array_sum($reviewDurations) / count($reviewDurations), 2)
            : null;

        $topUpApprovalTimeAvgMinutes = $averageReviewSeconds !== null
            ? round($averageReviewSeconds / 60, 2)
            : null;

        // 5. Orders by Service
        $ordersByServiceRaw = DB::table('orders as o')
            ->leftJoin('order_service_snapshots as s', 's.order_id', '=', 'o.id')
            ->where('o.created_at', '>=', $from)
            ->where('o.created_at', '<', $to)
            ->where('o.financial_status', 'paid')
            ->groupBy('o.service_id', 's.name_en', 's.name_ar')
            ->selectRaw('o.service_id, COALESCE(s.name_en, \'\') as name_en, COALESCE(s.name_ar, \'\') as name_ar, COUNT(*) as order_count, COALESCE(SUM(o.amount_paid_minor), 0) as volume_minor')
            ->orderByDesc('order_count')
            ->get();

        $ordersByService = [];
        foreach ($ordersByServiceRaw as $row) {
            $ordersByService[] = [
                'service_id' => (string) $row->service_id,
                'name_en' => (string) $row->name_en,
                'name_ar' => (string) $row->name_ar,
                'order_count' => (int) $row->order_count,
                'volume_minor' => (int) $row->volume_minor,
            ];
        }
        $servicePopularityBreakdown = $ordersByService;

        // 6. Executions metrics
        $executions = DB::table('service_executions')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->get();

        $createdExecutions = $executions->count();
        $completedExecutions = $executions->where('status', 'completed');
        $completedCount = $completedExecutions->count();

        $completedOrderPercentage = $createdExecutions > 0
            ? round(($completedCount / $createdExecutions) * 100, 2)
            : null;

        $fulfillmentDurations = [];
        foreach ($completedExecutions as $e) {
            $created = CarbonImmutable::parse($e->created_at);
            $last = CarbonImmutable::parse($e->last_status_at ?? $e->updated_at ?? $e->created_at);
            $fulfillmentDurations[] = abs((float) $last->diffInSeconds($created));
        }

        $averageFulfillmentSeconds = count($fulfillmentDurations) > 0
            ? round(array_sum($fulfillmentDurations) / count($fulfillmentDurations), 2)
            : null;

        $orderFulfillmentTimeAvgHours = $averageFulfillmentSeconds !== null
            ? round($averageFulfillmentSeconds / 3600, 2)
            : null;

        // 7. Customer Action Requests
        $customerActionVolume = (int) DB::table('customer_action_requests')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->count();

        $executionsWithAction = 0;
        if ($createdExecutions > 0) {
            $execIds = $executions->pluck('id')->all();
            $executionsWithAction = (int) DB::table('customer_action_requests')
                ->whereIn('execution_id', $execIds)
                ->distinct('execution_id')
                ->count('execution_id');
        }

        $actionRequestRate = $createdExecutions > 0
            ? round(($executionsWithAction / $createdExecutions) * 100, 2)
            : null;

        // 8. Repeat Customers & Traveler Reuse
        $ordersInPeriod = DB::table('orders')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->where('financial_status', 'paid')
            ->get();

        $accountOrders = $ordersInPeriod->groupBy('account_id');
        $totalAccountsWithOrders = $accountOrders->count();
        $repeatAccounts = $accountOrders->filter(fn ($ords) => $ords->count() > 1)->count();

        $repeatCustomerRate = $totalAccountsWithOrders > 0
            ? round(($repeatAccounts / $totalAccountsWithOrders) * 100, 2)
            : null;

        $travelerOrders = $ordersInPeriod->groupBy('traveler_id');
        $totalTravelersUsed = $travelerOrders->count();
        $reusedTravelers = $travelerOrders->filter(fn ($ords) => $ords->count() > 1)->count();

        $travelerReuseRate = $totalTravelersUsed > 0
            ? round(($reusedTravelers / $totalTravelersUsed) * 100, 2)
            : null;

        $activeCustomersCount = $totalAccountsWithOrders;

        // 9. Bank Channel Share
        $bankShareRaw = DB::table('topup_requests as t')
            ->leftJoin('company_bank_accounts as b', 'b.id', '=', 't.bank_account_id')
            ->where('t.submitted_at', '>=', $from)
            ->where('t.submitted_at', '<', $to)
            ->where('t.status', 'approved')
            ->groupBy('t.bank_account_id', 'b.bank_name_en', 'b.bank_name_ar')
            ->selectRaw('t.bank_account_id, COALESCE(b.bank_name_en, \'\') as bank_name_en, COALESCE(b.bank_name_ar, \'\') as bank_name_ar, COUNT(*) as topup_count, COALESCE(SUM(t.amount_minor), 0) as volume_minor')
            ->orderByDesc('volume_minor')
            ->get();

        $bankTopUpChannelShare = [];
        foreach ($bankShareRaw as $bRow) {
            $bankTopUpChannelShare[] = [
                'bank_account_id' => (string) $bRow->bank_account_id,
                'bank_name_en' => (string) $bRow->bank_name_en,
                'bank_name_ar' => (string) $bRow->bank_name_ar,
                'topup_count' => (int) $bRow->topup_count,
                'volume_minor' => (int) $bRow->volume_minor,
            ];
        }

        // 10. System Outbox Lag Seconds
        $outboxDelivered = DB::table('outbox_messages')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->whereNotNull('delivered_at')
            ->get();

        $outboxLags = [];
        foreach ($outboxDelivered as $m) {
            $c = CarbonImmutable::parse($m->created_at);
            $d = CarbonImmutable::parse($m->delivered_at);
            $outboxLags[] = abs((float) $d->diffInSeconds($c));
        }

        $systemOutboxLagSeconds = count($outboxLags) > 0
            ? (float) max($outboxLags)
            : null;

        return new ProductMetrics(
            filter: $filter,
            registeredUsers: $registeredUsers,
            savedTravelers: $savedTravelers,
            orderVolumeMinor: $orderVolumeMinor,
            totalOrders: $totalOrders,
            grossRevenueSdg: $grossRevenueSdg,
            topUpVolumeMinor: $topUpVolumeMinor,
            topUpCompletionRate: $topUpCompletionRate,
            averageReviewSeconds: $averageReviewSeconds,
            topUpApprovalRatio: $topUpApprovalRatio,
            topUpRejectionRate: $topUpRejectionRate,
            ordersByService: $ordersByService,
            servicePopularityBreakdown: $servicePopularityBreakdown,
            averageFulfillmentSeconds: $averageFulfillmentSeconds,
            orderFulfillmentTimeAvgHours: $orderFulfillmentTimeAvgHours,
            customerActionVolume: $customerActionVolume,
            actionRequestRate: $actionRequestRate,
            completedOrderPercentage: $completedOrderPercentage,
            travelerReuseRate: $travelerReuseRate,
            repeatCustomerRate: $repeatCustomerRate,
            activeCustomersCount: $activeCustomersCount,
            bankTopUpChannelShare: $bankTopUpChannelShare,
            systemOutboxLagSeconds: $systemOutboxLagSeconds,
            extra: [
                'topup_approval_time_avg_minutes' => $topUpApprovalTimeAvgMinutes,
            ],
        );
    }
}
