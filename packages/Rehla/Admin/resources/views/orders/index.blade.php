@extends('rehla-admin::layout')

@section('title', 'Orders')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Customer Orders</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Historical record of customer service bookings, wallet payments, and fulfillment states.
            </p>
        </div>
        <div class="admin-card-actions">
            <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
                Total Orders: <strong>{{ count($orders) }}</strong>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="admin-toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="admin-input" placeholder="Search by order number or service..." data-table-search="orders-table">
        </div>
    </div>

    {{-- Table --}}
    <div class="admin-table-wrapper">
        <table class="admin-table" id="orders-table">
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Service</th>
                    <th>Amount Paid (SDG)</th>
                    <th>Payment</th>
                    <th>Fulfillment</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <code style="font-size: 0.95rem; font-weight: 700; color: var(--admin-primary); background: var(--admin-bg-surface-elevated); padding: 0.2rem 0.5rem; border-radius: var(--radius-xs); border: 1px solid var(--admin-border-subtle);">
                                {{ $order->orderNumber }}
                            </code>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--admin-text-primary);">{{ $order->serviceNameEn }}</div>
                            <div style="font-size: 0.75rem; color: var(--admin-text-muted);">{{ $order->serviceNameAr }}</div>
                        </td>
                        <td>
                            <span style="font-weight: 800; font-family: monospace; font-size: 1rem; color: var(--admin-text-primary);">
                                {{ number_format($order->amountPaidMinor / 100, 2) }}
                            </span>
                            <span style="font-size: 0.75rem; color: var(--admin-text-muted);">SDG</span>
                        </td>
                        <td>
                            <span class="admin-badge badge-success">
                                {{ $order->financialStatus->value }}
                            </span>
                        </td>
                        <td>
                            <span class="admin-badge badge-info">
                                {{ $order->fulfillmentStatus->value }}
                            </span>
                        </td>
                        <td style="color: var(--admin-text-secondary); font-size: 0.82rem;">
                            {{ $order->createdAt->format('Y-m-d H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
                            No customer orders recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
