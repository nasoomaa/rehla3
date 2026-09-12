@extends('rehla-admin::layout')

@section('title', 'Service Executions')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Service Fulfillment Executions</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Operational lifecycle tracking, embassy processing stages, and traveler fulfillment tasks.
            </p>
        </div>
        <div class="admin-card-actions">
            <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
                Active Executions: <strong>{{ count($executions) }}</strong>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="admin-toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="admin-input" placeholder="Search by execution ID or order ID..." data-table-search="executions-table">
        </div>
    </div>

    {{-- Table --}}
    <div class="admin-table-wrapper">
        <table class="admin-table" id="executions-table">
            <thead>
                <tr>
                    <th>Execution ID</th>
                    <th>Order ID</th>
                    <th>Lifecycle Status</th>
                    <th>Last Status At</th>
                    <th style="text-align: end;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($executions as $exec)
                    <tr>
                        <td>
                            <code style="font-size: 0.85rem; font-weight: 700; color: var(--admin-primary); background: var(--admin-bg-surface-elevated); padding: 0.2rem 0.5rem; border-radius: var(--radius-xs); border: 1px solid var(--admin-border-subtle);">
                                {{ substr($exec->id, 0, 18) }}...
                            </code>
                        </td>
                        <td>
                            <code style="font-size: 0.8rem; color: var(--admin-text-muted);">
                                {{ substr($exec->orderId, 0, 18) }}...
                            </code>
                        </td>
                        <td>
                            @if($exec->status === 'completed')
                                <span class="admin-badge badge-success">Completed</span>
                            @elseif($exec->status === 'cancelled')
                                <span class="admin-badge badge-danger">Cancelled</span>
                            @elseif($exec->status === 'in_processing')
                                <span class="admin-badge badge-info">In Processing</span>
                            @else
                                <span class="admin-badge badge-warning">{{ $exec->status }}</span>
                            @endif
                        </td>
                        <td style="color: var(--admin-text-secondary); font-size: 0.82rem;">
                            {{ $exec->lastStatusAt->format('Y-m-d H:i') }}
                        </td>
                        <td style="text-align: end;">
                            <a href="/admin/service-executions/{{ $exec->id }}" class="admin-btn admin-btn-sm admin-btn-primary">
                                Inspect Details &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
                            No active service fulfillment executions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
