@extends('rehla-admin::layout')

@section('title', 'Orders History (Read-Only)')

@section('content')
<div class="card">
    <h2>Customer Orders</h2>
    <table>
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Service Name</th>
                <th>Amount Paid (SDG)</th>
                <th>Financial Status</th>
                <th>Fulfillment Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td><code>{{ $order->orderNumber }}</code></td>
                    <td><strong>{{ $order->serviceNameEn }}</strong></td>
                    <td>{{ number_format($order->amountPaidMinor / 100, 2) }}</td>
                    <td><span class="badge badge-success">{{ $order->financialStatus->value }}</span></td>
                    <td><span class="badge badge-info">{{ $order->fulfillmentStatus->value }}</span></td>
                    <td>{{ $order->createdAt->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="color: var(--text-muted); text-align: center;">No orders recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
