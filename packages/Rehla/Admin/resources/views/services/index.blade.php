@extends('rehla-admin::layout')

@section('title', 'Service Catalog')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Services Catalog</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Manage public visa & travel service offerings, pricing tiers, and descriptions.
            </p>
        </div>
        <div class="admin-card-actions">
            <button type="button" class="admin-btn admin-btn-primary" onclick="openDrawer('new-service-drawer')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Service</span>
            </button>
        </div>
    </div>

    {{-- Toolbar with Search and Filter --}}
    <div class="admin-toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="admin-input" placeholder="Search by service name..." data-table-search="services-table">
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
            Total: <strong>{{ count($services) }}</strong> services registered
        </div>
    </div>

    {{-- Table --}}
    <div class="admin-table-wrapper">
        <table class="admin-table" id="services-table">
            <thead>
                <tr>
                    <th>Service Name</th>
                    <th>Status</th>
                    <th>Price (SDG)</th>
                    <th>Duration</th>
                    <th style="text-align: end;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $srv)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--admin-text-primary);">{{ $srv->nameEn }}</div>
                            <div style="font-size: 0.8rem; color: var(--admin-text-muted);">{{ $srv->nameAr }}</div>
                        </td>
                        <td>
                            @if($srv->status->value === 'published')
                                <span class="admin-badge badge-success">Published</span>
                            @elseif($srv->status->value === 'draft')
                                <span class="admin-badge badge-warning">Draft</span>
                            @else
                                <span class="admin-badge badge-danger">Deactivated</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-weight: 700; font-family: monospace; font-size: 0.95rem;">
                                {{ number_format($srv->currentPriceMinor / 100, 2) }}
                            </span>
                            <span style="font-size: 0.75rem; color: var(--admin-text-muted);">SDG</span>
                        </td>
                        <td>{{ $srv->expectedDurationEn }}</td>
                        <td style="text-align: end;">
                            <div style="display: inline-flex; align-items: center; gap: 0.4rem;">
                                {{-- Edit Content Button --}}
                                <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="openDrawer('edit-service-drawer-{{ $srv->id }}')">
                                    Edit
                                </button>

                                {{-- Change Price Button --}}
                                <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="openModal('change-price-modal-{{ $srv->id }}')">
                                    Price
                                </button>

                                {{-- Publish Button --}}
                                @if($srv->status->value === 'draft')
                                    <form method="POST" action="/admin/services/{{ $srv->id }}/publish" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-sm admin-btn-success">Publish</button>
                                    </form>
                                @endif

                                {{-- Deactivate Button --}}
                                @if($srv->status->value === 'published')
                                    <form method="POST" action="/admin/services/{{ $srv->id }}/deactivate" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" onclick="return confirm('Are you sure you want to deactivate this service?')">Deactivate</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
                            No services found in catalog. Click "Add Service" to create your first draft.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Slide-over Drawer: Create New Service --}}
<div class="admin-drawer" id="new-service-drawer">
    <div class="drawer-header">
        <h3 class="drawer-title">Create New Service</h3>
        <button type="button" class="close-dialog-btn" onclick="closeDrawer('new-service-drawer')">&times;</button>
    </div>
    <form method="POST" action="/admin/services" style="display: flex; flex-direction: column; flex: 1;">
        @csrf
        <div class="drawer-body">
            <div class="admin-form-group">
                <label class="admin-label" for="name_en">Service Name (English)</label>
                <input type="text" id="name_en" name="name_en" class="admin-input" required placeholder="e.g. UAE Tourist Visa">
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="name_ar">Service Name (Arabic)</label>
                <input type="text" id="name_ar" name="name_ar" class="admin-input" required placeholder="e.g. تأشيرة سياحة الإمارات">
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="short_description_en">Short Description (EN)</label>
                <input type="text" id="short_description_en" name="short_description_en" class="admin-input" required>
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="short_description_ar">Short Description (AR)</label>
                <input type="text" id="short_description_ar" name="short_description_ar" class="admin-input" required>
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="detailed_description_en">Detailed Description (EN)</label>
                <textarea id="detailed_description_en" name="detailed_description_en" class="admin-textarea" rows="3" required></textarea>
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="detailed_description_ar">Detailed Description (AR)</label>
                <textarea id="detailed_description_ar" name="detailed_description_ar" class="admin-textarea" rows="3" required></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="admin-form-group">
                    <label class="admin-label" for="expected_duration_en">Duration (EN)</label>
                    <input type="text" id="expected_duration_en" name="expected_duration_en" class="admin-input" required placeholder="2-3 days">
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="expected_duration_ar">Duration (AR)</label>
                    <input type="text" id="expected_duration_ar" name="expected_duration_ar" class="admin-input" required placeholder="٢-٣ أيام">
                </div>
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="price_minor">Initial Price Minor Units (SDG)</label>
                <input type="number" id="price_minor" name="price_minor" class="admin-input" required placeholder="e.g. 5000000 = 50,000 SDG">
                <span style="font-size: 0.72rem; color: var(--admin-text-muted); display: block; margin-top: 0.25rem;">
                    1 SDG = 100 minor units (e.g. 100,000 SDG = 10000000).
                </span>
            </div>
        </div>
        <div class="drawer-footer">
            <button type="button" class="admin-btn admin-btn-ghost" onclick="closeDrawer('new-service-drawer')">Cancel</button>
            <button type="submit" class="admin-btn admin-btn-primary">Create Service Draft</button>
        </div>
    </form>
</div>

{{-- Per-Service Edit Drawers & Price Modals --}}
@foreach($services as $srv)
    {{-- Edit Content Drawer --}}
    <div class="admin-drawer" id="edit-service-drawer-{{ $srv->id }}">
        <div class="drawer-header">
            <h3 class="drawer-title">Edit Service Details</h3>
            <button type="button" class="close-dialog-btn" onclick="closeDrawer('edit-service-drawer-{{ $srv->id }}')">&times;</button>
        </div>
        <form method="POST" action="/admin/services/{{ $srv->id }}/content" style="display: flex; flex-direction: column; flex: 1;">
            @csrf
            @method('PUT')
            <div class="drawer-body">
                <div class="admin-form-group">
                    <label class="admin-label">Service Name (EN)</label>
                    <input type="text" name="name_en" class="admin-input" value="{{ $srv->nameEn }}" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label">Service Name (AR)</label>
                    <input type="text" name="name_ar" class="admin-input" value="{{ $srv->nameAr }}" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label">Short Description (EN)</label>
                    <input type="text" name="short_description_en" class="admin-input" value="{{ $srv->shortDescriptionEn }}" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label">Short Description (AR)</label>
                    <input type="text" name="short_description_ar" class="admin-input" value="{{ $srv->shortDescriptionAr }}" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label">Detailed Description (EN)</label>
                    <textarea name="detailed_description_en" class="admin-textarea" rows="3" required>{{ $srv->detailedDescriptionEn }}</textarea>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label">Detailed Description (AR)</label>
                    <textarea name="detailed_description_ar" class="admin-textarea" rows="3" required>{{ $srv->detailedDescriptionAr }}</textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="admin-form-group">
                        <label class="admin-label">Duration (EN)</label>
                        <input type="text" name="expected_duration_en" class="admin-input" value="{{ $srv->expectedDurationEn }}" required>
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-label">Duration (AR)</label>
                        <input type="text" name="expected_duration_ar" class="admin-input" value="{{ $srv->expectedDurationAr }}" required>
                    </div>
                </div>
            </div>
            <div class="drawer-footer">
                <button type="button" class="admin-btn admin-btn-ghost" onclick="closeDrawer('edit-service-drawer-{{ $srv->id }}')">Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary">Update Details</button>
            </div>
        </form>
    </div>

    {{-- Change Price Modal --}}
    <div class="admin-modal" id="change-price-modal-{{ $srv->id }}">
        <div class="modal-header">
            <h3 class="modal-title">Change Price — {{ $srv->nameEn }}</h3>
            <button type="button" class="close-dialog-btn" onclick="closeModal('change-price-modal-{{ $srv->id }}')">&times;</button>
        </div>
        <form method="POST" action="/admin/services/{{ $srv->id }}/price">
            @csrf
            <div class="modal-body">
                <div style="margin-bottom: 1rem; padding: 0.85rem; background: var(--admin-bg-surface-elevated); border-radius: var(--radius-sm);">
                    <div style="font-size: 0.8rem; color: var(--admin-text-muted);">Current Price</div>
                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--admin-text-primary);">
                        {{ number_format($srv->currentPriceMinor / 100, 2) }} SDG
                    </div>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="new_price_minor_{{ $srv->id }}">New Price in Minor Units (SDG)</label>
                    <input type="number" id="new_price_minor_{{ $srv->id }}" name="new_price_minor" class="admin-input" value="{{ $srv->currentPriceMinor }}" required min="0">
                    <span style="font-size: 0.72rem; color: var(--admin-text-muted); display: block; margin-top: 0.25rem;">
                        Scale: 100 minor units = 1 SDG.
                    </span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="admin-btn admin-btn-ghost" onclick="closeModal('change-price-modal-{{ $srv->id }}')">Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary">Save New Price</button>
            </div>
        </form>
    </div>
@endforeach
@endsection
