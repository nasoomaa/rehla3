@extends('rehla-admin::layout')

@section('title', 'Company Bank Accounts')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Company Bank Accounts</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Manage official receiving bank accounts for customer wallet top-up transfers (Bank of Khartoum, FIB, etc.).
            </p>
        </div>
        <div class="admin-card-actions">
            <button type="button" class="admin-btn admin-btn-primary" onclick="openDrawer('new-account-drawer')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Bank Account</span>
            </button>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="admin-toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="admin-input" placeholder="Search by bank or account number..." data-table-search="bank-accounts-table">
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
            Active accounts: <strong>{{ collect($bankAccounts)->where('active', true)->count() }}</strong> / {{ count($bankAccounts) }}
        </div>
    </div>

    {{-- Table --}}
    <div class="admin-table-wrapper">
        <table class="admin-table" id="bank-accounts-table">
            <thead>
                <tr>
                    <th>Bank Name</th>
                    <th>Beneficiary</th>
                    <th>Account Number</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th style="text-align: end;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bankAccounts as $acc)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--admin-text-primary);">{{ $acc->bankNameEn }}</div>
                            <div style="font-size: 0.8rem; color: var(--admin-text-muted);">{{ $acc->bankNameAr }}</div>
                        </td>
                        <td style="color: var(--admin-text-secondary);">{{ $acc->beneficiaryName }}</td>
                        <td>
                            <code style="background: var(--admin-bg-surface-elevated); padding: 0.25rem 0.6rem; border-radius: var(--radius-xs); font-size: 0.95rem; border: 1px solid var(--admin-border-subtle); color: var(--admin-primary); font-weight: 700;">
                                {{ $acc->accountNumber }}
                            </code>
                        </td>
                        <td>
                            @if($acc->active)
                                <span class="admin-badge badge-success">Active</span>
                            @else
                                <span class="admin-badge badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td style="color: var(--admin-text-muted); font-weight: 500;">{{ $acc->sortOrder }}</td>
                        <td style="text-align: end;">
                            <div style="display: inline-flex; align-items: center; gap: 0.4rem;">
                                {{-- Edit Modal Trigger --}}
                                <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="openModal('edit-account-modal-{{ $acc->id }}')">
                                    Edit
                                </button>

                                {{-- Toggle Active / Deactivate --}}
                                @if($acc->active)
                                    <form method="POST" action="/admin/bank-accounts/{{ $acc->id }}/deactivate" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" onclick="return confirm('Are you sure you want to deactivate this bank account?')">
                                            Deactivate
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
                            No company bank accounts configured. Click "Add Bank Account" to configure one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Slide-over Drawer: Create Bank Account --}}
<div class="admin-drawer" id="new-account-drawer">
    <div class="drawer-header">
        <h3 class="drawer-title">Add Company Bank Account</h3>
        <button type="button" class="close-dialog-btn" onclick="closeDrawer('new-account-drawer')">&times;</button>
    </div>
    <form method="POST" action="/admin/bank-accounts" style="display: flex; flex-direction: column; flex: 1;">
        @csrf
        <div class="drawer-body">
            <div class="admin-form-group">
                <label class="admin-label" for="bank_name_en">Bank Name (English)</label>
                <input type="text" id="bank_name_en" name="bank_name_en" class="admin-input" required placeholder="e.g. Bank of Khartoum">
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="bank_name_ar">Bank Name (Arabic)</label>
                <input type="text" id="bank_name_ar" name="bank_name_ar" class="admin-input" required placeholder="e.g. بنك الخرطوم">
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="beneficiary_name">Beneficiary Name</label>
                <input type="text" id="beneficiary_name" name="beneficiary_name" class="admin-input" required placeholder="e.g. Rehla Travel Services Co.">
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="account_number">Account Number</label>
                <input type="text" id="account_number" name="account_number" class="admin-input" required placeholder="e.g. 1987654321">
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="sort_order">Display Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" class="admin-input" value="1" min="0">
            </div>
        </div>
        <div class="drawer-footer">
            <button type="button" class="admin-btn admin-btn-ghost" onclick="closeDrawer('new-account-drawer')">Cancel</button>
            <button type="submit" class="admin-btn admin-btn-primary">Save Account</button>
        </div>
    </form>
</div>

{{-- Per-Account Edit Modals --}}
@foreach($bankAccounts as $acc)
    <div class="admin-modal" id="edit-account-modal-{{ $acc->id }}">
        <div class="modal-header">
            <h3 class="modal-title">Edit Bank Account — {{ $acc->bankNameEn }}</h3>
            <button type="button" class="close-dialog-btn" onclick="closeModal('edit-account-modal-{{ $acc->id }}')">&times;</button>
        </div>
        <form method="POST" action="/admin/bank-accounts/{{ $acc->id }}">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="admin-form-group">
                    <label class="admin-label">Bank Name (English)</label>
                    <input type="text" name="bank_name_en" class="admin-input" value="{{ $acc->bankNameEn }}" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label">Bank Name (Arabic)</label>
                    <input type="text" name="bank_name_ar" class="admin-input" value="{{ $acc->bankNameAr }}" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label">Beneficiary Name</label>
                    <input type="text" name="beneficiary_name" class="admin-input" value="{{ $acc->beneficiaryName }}" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label">Account Number</label>
                    <input type="text" name="account_number" class="admin-input" value="{{ $acc->accountNumber }}" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label">Sort Order</label>
                    <input type="number" name="sort_order" class="admin-input" value="{{ $acc->sortOrder }}" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="admin-btn admin-btn-ghost" onclick="closeModal('edit-account-modal-{{ $acc->id }}')">Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary">Update Account</button>
            </div>
        </form>
    </div>
@endforeach
@endsection
