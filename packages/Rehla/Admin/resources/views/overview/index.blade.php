@extends('rehla-admin::layout')

@section('title', 'Platform Operations Overview')

@section('content')
<div class="grid-stats">
    <div class="stat-card">
        <div class="stat-title">Total Orders</div>
        <div class="stat-value">{{ number_format($metrics->totalOrders) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Gross Revenue (SDG)</div>
        <div class="stat-value">{{ number_format($metrics->grossRevenueSdg / 100, 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Registered Users</div>
        <div class="stat-value">{{ number_format($metrics->registeredUsers) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Saved Travelers</div>
        <div class="stat-value">{{ number_format($metrics->savedTravelers) }}</div>
    </div>
</div>

<div class="grid-stats">
    <div class="stat-card">
        <div class="stat-title">Top-Up Completion Rate</div>
        <div class="stat-value">{{ $metrics->topUpCompletionRate !== null ? $metrics->topUpCompletionRate . '%' : 'N/A' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Top-Up Volume (SDG)</div>
        <div class="stat-value">{{ number_format($metrics->topUpVolumeMinor / 100, 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Completed Orders %</div>
        <div class="stat-value">{{ $metrics->completedOrderPercentage !== null ? $metrics->completedOrderPercentage . '%' : 'N/A' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Action Request Rate</div>
        <div class="stat-value">{{ $metrics->actionRequestRate !== null ? $metrics->actionRequestRate . '%' : 'N/A' }}</div>
    </div>
</div>

<div class="card">
    <h2>Popular Services Breakdown</h2>
    @if(empty($metrics->servicePopularityBreakdown))
        <p style="color: var(--text-muted);">No orders recorded in the current period.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Service Name (EN)</th>
                    <th>Service Name (AR)</th>
                    <th>Order Count</th>
                    <th>Total Volume (SDG)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($metrics->servicePopularityBreakdown as $srv)
                    <tr>
                        <td>{{ $srv['name_en'] }}</td>
                        <td>{{ $srv['name_ar'] }}</td>
                        <td>{{ $srv['order_count'] }}</td>
                        <td>{{ number_format($srv['volume_minor'] / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
