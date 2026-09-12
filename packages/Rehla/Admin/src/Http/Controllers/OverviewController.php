<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Rehla\Reporting\Data\MetricFilter;
use Rehla\Reporting\Queries\GetProductMetrics;

final class OverviewController extends Controller
{
    public function index(GetProductMetrics $getProductMetrics): View
    {
        $filter = new MetricFilter(
            fromUtc: CarbonImmutable::now()->subDays(30)->startOfDay(),
            toUtc: CarbonImmutable::now()->endOfDay(),
        );

        $metrics = $getProductMetrics->handle($filter);

        return view('rehla-admin::overview.index', [
            'metrics' => $metrics,
        ]);
    }
}
