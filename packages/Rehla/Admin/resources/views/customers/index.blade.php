@extends('rehla-admin::layout')

@section('title', 'Customers Directory')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Customers Directory</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Registered customer accounts, contact details, and account identifiers.
            </p>
        </div>
        <div class="admin-card-actions">
            <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
                Total Customers: <strong>{{ count($customers) }}</strong>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="admin-toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="admin-input" placeholder="Search customers by name, email, or ID..." data-table-search="customers-table">
        </div>
    </div>

    {{-- Table --}}
    <div class="admin-table-wrapper">
        <table class="admin-table" id="customers-table">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Email Address</th>
                    <th>Customer ID</th>
                    <th style="text-align: end;">Profile</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.65rem;">
                                <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    {{ strtoupper(substr($customer['name'] ?? 'C', 0, 1)) }}
                                </div>
                                <span style="font-weight: 600; color: var(--admin-text-primary);">{{ $customer['name'] }}</span>
                            </div>
                        </td>
                        <td style="color: var(--admin-text-secondary);">{{ $customer['email'] }}</td>
                        <td>
                            <code style="font-size: 0.8rem; color: var(--admin-text-muted);">
                                {{ $customer['id'] }}
                            </code>
                        </td>
                        <td style="text-align: end;">
                            <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="openDrawer('customer-drawer-{{ $customer['id'] }}')">
                                View Profile
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
                            No registered customers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Customer Profile Drawers --}}
@foreach($customers as $customer)
    <div class="admin-drawer" id="customer-drawer-{{ $customer['id'] }}">
        <div class="drawer-header">
            <h3 class="drawer-title">Customer Profile</h3>
            <button type="button" class="close-dialog-btn" onclick="closeDrawer('customer-drawer-{{ $customer['id'] }}')">&times;</button>
        </div>
        <div class="drawer-body">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding: 1.25rem; background: var(--admin-bg-surface-elevated); border-radius: var(--radius-md);">
                <div class="user-avatar" style="width: 52px; height: 52px; font-size: 1.3rem;">
                    {{ strtoupper(substr($customer['name'] ?? 'C', 0, 1)) }}
                </div>
                <div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: var(--admin-text-primary);">{{ $customer['name'] }}</div>
                    <div style="font-size: 0.85rem; color: var(--admin-text-muted);">{{ $customer['email'] }}</div>
                </div>
            </div>

            <div class="admin-form-group">
                <label class="admin-label">Customer ID</label>
                <code style="display: block; padding: 0.5rem; background: var(--admin-bg-canvas); border: 1px solid var(--admin-border-subtle); border-radius: var(--radius-xs); color: var(--admin-primary);">
                    {{ $customer['id'] }}
                </code>
            </div>
            <div class="admin-form-group">
                <label class="admin-label">Account Guard</label>
                <input type="text" class="admin-input" value="Customer (web / api)" disabled>
            </div>
        </div>
        <div class="drawer-footer">
            <button type="button" class="admin-btn admin-btn-secondary" onclick="closeDrawer('customer-drawer-{{ $customer['id'] }}')">Close</button>
        </div>
    </div>
@endforeach
@endsection
