@extends('rehla-admin::layout')

@section('title', 'Wallets & Treasury')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Customer Financial Wallets</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Read-only view of customer prepaid balances in SDG, non-negative invariants, and lock versions.
            </p>
        </div>
        <div class="admin-card-actions">
            <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
                Active Wallets: <strong>{{ count($wallets) }}</strong>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="admin-toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="admin-input" placeholder="Search by wallet ID or account ID..." data-table-search="wallets-table">
        </div>
    </div>

    {{-- Table --}}
    <div class="admin-table-wrapper">
        <table class="admin-table" id="wallets-table">
            <thead>
                <tr>
                    <th>Wallet Identifier</th>
                    <th>Account Identifier</th>
                    <th>Currency</th>
                    <th>Available Balance (SDG)</th>
                    <th>Lock Version</th>
                </tr>
            </thead>
            <tbody>
                @forelse($wallets as $wallet)
                    <tr>
                        <td>
                            <code style="font-size: 0.85rem; font-weight: 700; color: var(--admin-primary); background: var(--admin-bg-surface-elevated); padding: 0.2rem 0.5rem; border-radius: var(--radius-xs); border: 1px solid var(--admin-border-subtle);">
                                {{ $wallet->id }}
                            </code>
                        </td>
                        <td>
                            <code style="font-size: 0.82rem; color: var(--admin-text-muted);">
                                {{ $wallet->accountId }}
                            </code>
                        </td>
                        <td>
                            <span class="admin-badge badge-info">{{ $wallet->currency }}</span>
                        </td>
                        <td>
                            <span style="font-weight: 800; font-family: monospace; font-size: 1.05rem; color: var(--admin-success);">
                                {{ number_format($wallet->balanceMinor / 100, 2) }}
                            </span>
                            <span style="font-size: 0.75rem; color: var(--admin-text-muted);">SDG</span>
                        </td>
                        <td>
                            <span class="admin-badge badge-neutral">v{{ $wallet->lockVersion }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
                            No customer wallets recorded.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
