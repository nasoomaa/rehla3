@extends('rehla-admin::layout')

@section('title', 'Execution Details')

@section('content')
<div style="margin-bottom: 1.25rem;">
    <a href="/admin/service-executions" style="color: var(--admin-primary); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 600;">
        &larr; Back to Service Executions
    </a>
</div>

{{-- Top Summary Card --}}
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <div style="font-size: 0.75rem; color: var(--admin-text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">
                Execution Record
            </div>
            <h1 class="admin-card-title" style="font-family: monospace; font-size: 1.25rem; margin-top: 0.25rem;">
                {{ $execution->id }}
            </h1>
        </div>
        <div class="admin-card-actions">
            @if($execution->status === 'completed')
                <span class="admin-badge badge-success" style="font-size: 0.85rem; padding: 0.35rem 0.85rem;">Completed</span>
            @elseif($execution->status === 'cancelled')
                <span class="admin-badge badge-danger" style="font-size: 0.85rem; padding: 0.35rem 0.85rem;">Cancelled</span>
            @elseif($execution->status === 'in_processing')
                <span class="admin-badge badge-info" style="font-size: 0.85rem; padding: 0.35rem 0.85rem;">In Processing</span>
            @else
                <span class="admin-badge badge-warning" style="font-size: 0.85rem; padding: 0.35rem 0.85rem;">{{ $execution->status }}</span>
            @endif
        </div>
    </div>

    {{-- Metadata Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--admin-border-subtle);">
        <div>
            <div style="font-size: 0.75rem; color: var(--admin-text-muted); text-transform: uppercase; font-weight: 600;">Order ID</div>
            <code style="font-size: 0.85rem; color: var(--admin-primary); display: block; margin-top: 0.25rem;">{{ $execution->orderId }}</code>
        </div>
        <div>
            <div style="font-size: 0.75rem; color: var(--admin-text-muted); text-transform: uppercase; font-weight: 600;">Customer Account ID</div>
            <code style="font-size: 0.85rem; color: var(--admin-text-secondary); display: block; margin-top: 0.25rem;">{{ $execution->accountId }}</code>
        </div>
        <div>
            <div style="font-size: 0.75rem; color: var(--admin-text-muted); text-transform: uppercase; font-weight: 600;">Traveler Profile ID</div>
            <code style="font-size: 0.85rem; color: var(--admin-text-secondary); display: block; margin-top: 0.25rem;">{{ $execution->travelerId }}</code>
        </div>
        <div>
            <div style="font-size: 0.75rem; color: var(--admin-text-muted); text-transform: uppercase; font-weight: 600;">Last Status Update</div>
            <div style="font-size: 0.85rem; color: var(--admin-text-primary); font-weight: 600; margin-top: 0.25rem;">
                {{ $execution->lastStatusAt->format('Y-m-d H:i') }}
            </div>
        </div>
    </div>
</div>

{{-- Action Panels Grid --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    {{-- Transition Status Form --}}
    <div class="admin-card" style="margin-bottom: 0;">
        <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: var(--admin-text-primary); display: flex; align-items: center; gap: 0.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            <span>Transition Lifecycle Status</span>
        </h2>
        <form method="POST" action="/admin/service-executions/{{ $execution->id }}/transition">
            @csrf
            <div class="admin-form-group">
                <label class="admin-label" for="target_status">Target Lifecycle State</label>
                <select id="target_status" name="target_status" class="admin-select" required>
                    <option value="">-- Select Target Status --</option>
                    <option value="under_review">under_review</option>
                    <option value="in_processing">in_processing</option>
                    <option value="completed">completed</option>
                    <option value="cancelled">cancelled</option>
                </select>
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="reason">Operational Reason / Notes</label>
                <input type="text" id="reason" name="reason" class="admin-input" placeholder="e.g. Documents submitted to embassy">
            </div>
            <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%;">
                Transition State
            </button>
        </form>
    </div>

    {{-- Request Customer Action Form --}}
    <div class="admin-card" style="margin-bottom: 0;">
        <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: var(--admin-text-primary); display: flex; align-items: center; gap: 0.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>Request Customer Action</span>
        </h2>
        <form method="POST" action="/admin/service-executions/{{ $execution->id }}/customer-action">
            @csrf
            <div class="admin-form-group">
                <label class="admin-label" for="description_en">Description (English)</label>
                <input type="text" id="description_en" name="description_en" class="admin-input" required placeholder="e.g. Passport copy was blurry, re-upload needed">
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="description_ar">Description (Arabic)</label>
                <input type="text" id="description_ar" name="description_ar" class="admin-input" required placeholder="e.g. صورة الجواز غير واضحة، يرجى إعادة الرفع">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="admin-form-group">
                    <label class="admin-label" for="required_document_purpose">Doc Purpose</label>
                    <input type="text" id="required_document_purpose" name="required_document_purpose" class="admin-input" placeholder="e.g. passport_photo">
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="due_in_hours">Due in Hours</label>
                    <input type="number" id="due_in_hours" name="due_in_hours" class="admin-input" value="48" min="1">
                </div>
            </div>
            <button type="submit" class="admin-btn admin-btn-secondary" style="width: 100%; border-color: var(--admin-warning); color: var(--admin-warning);">
                Issue Action Request
            </button>
        </form>
    </div>
</div>

{{-- Internal Notes & Transition History --}}
<div class="admin-card">
    <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: var(--admin-text-primary);">
        Internal Operational Notes
    </h2>
    <form method="POST" action="/admin/service-executions/{{ $execution->id }}/note" style="margin-bottom: 2rem;">
        @csrf
        <div class="admin-form-group">
            <input type="text" name="body" class="admin-input" required placeholder="Add confidential operational note regarding embassy communication, visa numbers...">
        </div>
        <button type="submit" class="admin-btn admin-btn-secondary">Add Internal Note</button>
    </form>

    <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: var(--admin-text-primary);">
        Lifecycle Transition Timeline
    </h2>
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Actor</th>
                    <th>State Transition</th>
                    <th>Reason / Notes</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                @forelse($execution->history as $h)
                    <tr>
                        <td>
                            <span class="admin-badge badge-neutral">{{ $h->actorType }}</span>
                        </td>
                        <td>
                            <code style="font-weight: 700; color: var(--admin-primary);">
                                {{ $h->fromStatus ?? 'initial' }} &rarr; {{ $h->toStatus }}
                            </code>
                        </td>
                        <td style="color: var(--admin-text-secondary);">
                            {{ $h->reason ?? '—' }}
                        </td>
                        <td style="color: var(--admin-text-muted); font-size: 0.82rem;">
                            {{ $h->createdAt->format('Y-m-d H:i:s') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--admin-text-muted); padding: 2rem;">
                            No state transitions recorded for this execution.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
