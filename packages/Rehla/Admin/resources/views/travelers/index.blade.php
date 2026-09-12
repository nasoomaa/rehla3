@extends('rehla-admin::layout')

@section('title', 'Travelers CRM')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Travelers Directory</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Saved passenger profiles and masked travel identification credentials.
            </p>
        </div>
        <div class="admin-card-actions">
            <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
                Total Travelers: <strong>{{ count($travelers) }}</strong>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="admin-toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="admin-input" placeholder="Search by traveler name or passport..." data-table-search="travelers-table">
        </div>
    </div>

    {{-- Table --}}
    <div class="admin-table-wrapper">
        <table class="admin-table" id="travelers-table">
            <thead>
                <tr>
                    <th>Traveler Name</th>
                    <th>Masked Passport</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Passport Expiry</th>
                    <th>Profile ID</th>
                </tr>
            </thead>
            <tbody>
                @forelse($travelers as $traveler)
                    <tr>
                        <td>
                            <strong style="color: var(--admin-text-primary); font-size: 0.95rem;">
                                {{ $traveler['fullName'] }}
                            </strong>
                        </td>
                        <td>
                            <code style="font-family: monospace; font-size: 0.95rem; font-weight: 700; color: var(--admin-primary); background: var(--admin-bg-surface-elevated); padding: 0.25rem 0.6rem; border-radius: var(--radius-xs); border: 1px solid var(--admin-border-subtle);">
                                {{ $traveler['maskedPassport'] }}
                            </code>
                        </td>
                        <td style="color: var(--admin-text-secondary);">{{ $traveler['dateOfBirth'] }}</td>
                        <td>
                            <span class="admin-badge badge-neutral">{{ $traveler['gender'] }}</span>
                        </td>
                        <td style="color: var(--admin-text-secondary);">{{ $traveler['passportExpiresAt'] }}</td>
                        <td>
                            <code style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                {{ substr($traveler['id'], 0, 13) }}...
                            </code>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
                            No passenger records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
