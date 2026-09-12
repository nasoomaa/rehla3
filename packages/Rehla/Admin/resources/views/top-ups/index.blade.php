@extends('rehla-admin::layout')

@section('title', 'Top-up Requests Review')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Bank Top-Up Verification Queue</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Review and verify incoming customer bank deposit receipts to credit customer wallets.
            </p>
        </div>
        <div class="admin-card-actions">
            <span class="admin-badge badge-warning" style="font-size: 0.78rem;">
                {{ collect($topUps)->where('status.value', 'under_review')->count() }} Pending Review
            </span>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="admin-toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="admin-input" placeholder="Search by reference or ID..." data-table-search="topups-table">
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
            Total requests: <strong>{{ count($topUps) }}</strong>
        </div>
    </div>

    {{-- Table --}}
    <div class="admin-table-wrapper">
        <table class="admin-table" id="topups-table">
            <thead>
                <tr>
                    <th>Reference / ID</th>
                    <th>Amount (SDG)</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th style="text-align: end;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topUps as $topUp)
                    <tr>
                        <td>
                            <code style="font-size: 0.95rem; font-weight: 700; color: var(--admin-primary); background: var(--admin-bg-surface-elevated); padding: 0.2rem 0.5rem; border-radius: var(--radius-xs); border: 1px solid var(--admin-border-subtle);">
                                {{ $topUp->transactionReference }}
                            </code>
                            <div style="color: var(--admin-text-muted); font-size: 0.72rem; margin-top: 0.25rem;">
                                ID: {{ substr($topUp->id, 0, 18) }}...
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 800; font-family: monospace; font-size: 1.05rem; color: var(--admin-success);">
                                {{ number_format($topUp->amountMinor / 100, 2) }}
                            </span>
                            <span style="font-size: 0.75rem; color: var(--admin-text-muted);">SDG</span>
                        </td>
                        <td>
                            @if($topUp->status->value === 'approved')
                                <span class="admin-badge badge-success">Approved</span>
                            @elseif($topUp->status->value === 'rejected')
                                <span class="admin-badge badge-danger">Rejected</span>
                                @if($topUp->rejectionReason)
                                    <div style="color: var(--admin-danger); font-size: 0.75rem; margin-top: 0.25rem;">
                                        {{ $topUp->rejectionReason }}
                                    </div>
                                @endif
                            @else
                                <span class="admin-badge badge-warning">Under Review</span>
                            @endif
                        </td>
                        <td style="color: var(--admin-text-secondary); font-size: 0.82rem;">
                            {{ $topUp->submittedAt->format('Y-m-d H:i') }}
                        </td>
                        <td style="text-align: end;">
                            @if($topUp->status->value === 'under_review')
                                <div style="display: inline-flex; align-items: center; gap: 0.4rem;">
                                    <button type="button" class="admin-btn admin-btn-sm admin-btn-success" onclick="openModal('approve-topup-modal-{{ $topUp->id }}')">
                                        Approve
                                    </button>
                                    <button type="button" class="admin-btn admin-btn-sm admin-btn-danger" onclick="openModal('reject-topup-modal-{{ $topUp->id }}')">
                                        Reject
                                    </button>
                                </div>
                            @else
                                <span class="admin-badge badge-neutral">Resolved</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
                            No bank top-up requests currently waiting in the verification queue.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Per-TopUp Approve & Reject Modals --}}
@foreach($topUps as $topUp)
    @if($topUp->status->value === 'under_review')
        {{-- Approve Modal --}}
        <div class="admin-modal" id="approve-topup-modal-{{ $topUp->id }}">
            <div class="modal-header">
                <h3 class="modal-title">Approve Top-Up — {{ $topUp->transactionReference }}</h3>
                <button type="button" class="close-dialog-btn" onclick="closeModal('approve-topup-modal-{{ $topUp->id }}')">&times;</button>
            </div>
            <form method="POST" action="/admin/top-up-requests/{{ $topUp->id }}/approve">
                @csrf
                <div class="modal-body">
                    <div style="padding: 1rem; background: var(--admin-success-light); border: 1px solid var(--admin-success); border-radius: var(--radius-sm); margin-bottom: 1.25rem;">
                        <div style="font-size: 0.8rem; color: var(--admin-text-muted);">Credit Amount</div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--admin-success);">
                            {{ number_format($topUp->amountMinor / 100, 2) }} SDG
                        </div>
                        <div style="font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 0.25rem;">
                            Approving will immediately credit the customer's wallet and post an immutable ledger entry.
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn admin-btn-ghost" onclick="closeModal('approve-topup-modal-{{ $topUp->id }}')">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-success">Confirm Approval</button>
                </div>
            </form>
        </div>

        {{-- Reject Modal --}}
        <div class="admin-modal" id="reject-topup-modal-{{ $topUp->id }}">
            <div class="modal-header">
                <h3 class="modal-title">Reject Top-Up — {{ $topUp->transactionReference }}</h3>
                <button type="button" class="close-dialog-btn" onclick="closeModal('reject-topup-modal-{{ $topUp->id }}')">&times;</button>
            </div>
            <form method="POST" action="/admin/top-up-requests/{{ $topUp->id }}/reject">
                @csrf
                <div class="modal-body">
                    <div class="admin-form-group">
                        <label class="admin-label" for="reason_{{ $topUp->id }}">Reason for Rejection</label>
                        <textarea id="reason_{{ $topUp->id }}" name="reason" class="admin-textarea" rows="3" required placeholder="e.g. Deposit could not be verified with bank or receipt unreadable."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn admin-btn-ghost" onclick="closeModal('reject-topup-modal-{{ $topUp->id }}')">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    @endif
@endforeach
@endsection
