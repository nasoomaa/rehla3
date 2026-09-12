@extends('rehla-admin::layout')

@section('title', 'Platform Operations Overview')

@section('content')
{{-- Primary High-Level Operational Metrics Grid --}}
<div class="admin-grid-stats">
    <div class="stat-card">
        <div class="stat-title">Total Orders</div>
        <div class="stat-value">{{ number_format($metrics->totalOrders) }}</div>
        <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.35rem;">Lifetime booking volume</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Gross Revenue</div>
        <div class="stat-value" style="color: var(--admin-success); font-family: monospace;">
            {{ number_format($metrics->grossRevenueSdg / 100, 2) }}
            <span style="font-size: 0.85rem; font-weight: 600; color: var(--admin-text-muted);">SDG</span>
        </div>
        <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.35rem;">Completed payments</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Registered Customers</div>
        <div class="stat-value">{{ number_format($metrics->registeredUsers) }}</div>
        <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.35rem;">Active accounts</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Saved Travelers</div>
        <div class="stat-value">{{ number_format($metrics->savedTravelers) }}</div>
        <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.35rem;">Verified passenger profiles</div>
    </div>
</div>

{{-- Secondary Efficiency & Fulfillment KPIs --}}
<div class="admin-grid-stats">
    <div class="stat-card">
        <div class="stat-title">Top-Up Completion Rate</div>
        <div class="stat-value" style="color: var(--admin-info);">
            {{ $metrics->topUpCompletionRate !== null ? $metrics->topUpCompletionRate . '%' : 'N/A' }}
        </div>
        <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.35rem;">Verified bank deposits</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Top-Up Volume</div>
        <div class="stat-value" style="font-family: monospace;">
            {{ number_format($metrics->topUpVolumeMinor / 100, 2) }}
            <span style="font-size: 0.85rem; font-weight: 600; color: var(--admin-text-muted);">SDG</span>
        </div>
        <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.35rem;">Total wallet deposits</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Completed Orders %</div>
        <div class="stat-value" style="color: var(--admin-success);">
            {{ $metrics->completedOrderPercentage !== null ? $metrics->completedOrderPercentage . '%' : 'N/A' }}
        </div>
        <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.35rem;">Fulfillment success rate</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Action Request Rate</div>
        <div class="stat-value" style="color: var(--admin-warning);">
            {{ $metrics->actionRequestRate !== null ? $metrics->actionRequestRate . '%' : 'N/A' }}
        </div>
        <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.35rem;">Requires customer documents</div>
    </div>
</div>

{{-- Popular Services Breakdown Card --}}
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Popular Services Performance</h2>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Breakdown of booking counts and financial volume by service category.
            </p>
        </div>
    </div>

    @if(empty($metrics->servicePopularityBreakdown))
        <div style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
            No orders recorded in the current analytical period.
        </div>
    @else
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Service Name (EN)</th>
                        <th>Service Name (AR)</th>
                        <th>Bookings Count</th>
                        <th style="text-align: end;">Total Volume (SDG)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($metrics->servicePopularityBreakdown as $srv)
                        <tr>
                            <td><strong>{{ $srv['name_en'] }}</strong></td>
                            <td style="color: var(--admin-text-muted);">{{ $srv['name_ar'] }}</td>
                            <td>
                                <span class="admin-badge badge-info">{{ $srv['order_count'] }} orders</span>
                            </td>
                            <td style="text-align: end; font-family: monospace; font-weight: 700; color: var(--admin-text-primary);">
                                {{ number_format($srv['volume_minor'] / 100, 2) }} SDG
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
