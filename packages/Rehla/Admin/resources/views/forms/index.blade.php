@extends('rehla-admin::layout')

@section('title', 'Application Forms')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Application Forms Management</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Build, edit, and publish dynamic traveler application forms attached to catalog services.
            </p>
        </div>
        <div class="admin-card-actions">
            <button type="button" class="admin-btn admin-btn-primary" onclick="openDrawer('new-form-drawer')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Create Form Draft</span>
            </button>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="admin-toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="admin-input" placeholder="Search forms by service or version..." data-table-search="forms-table">
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
            Total versions: <strong>{{ count($forms) }}</strong>
        </div>
    </div>

    {{-- Table --}}
    <div class="admin-table-wrapper">
        <table class="admin-table" id="forms-table">
            <thead>
                <tr>
                    <th>Service Name</th>
                    <th>Version</th>
                    <th>Fields</th>
                    <th>Status</th>
                    <th>Checksum</th>
                    <th style="text-align: end;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($forms as $form)
                    @php
                        $matchedService = collect($services)->firstWhere('id', $form->serviceId);
                    @endphp
                    <tr>
                        <td>
                            @if($matchedService)
                                <div style="font-weight: 600; color: var(--admin-text-primary);">{{ $matchedService->nameEn }}</div>
                                <div style="font-size: 0.8rem; color: var(--admin-text-muted);">{{ $matchedService->nameAr }}</div>
                            @else
                                <code style="font-size: 0.8rem;">{{ substr($form->serviceId, 0, 13) }}...</code>
                            @endif
                        </td>
                        <td>
                            <span style="font-weight: 700; font-family: monospace; background: var(--admin-bg-surface-elevated); padding: 0.2rem 0.5rem; border-radius: var(--radius-xs); border: 1px solid var(--admin-border-subtle);">
                                v{{ $form->version }}
                            </span>
                        </td>
                        <td>
                            <span class="admin-badge badge-info">{{ count($form->fields) }} fields</span>
                        </td>
                        <td>
                            @if($form->status->value === 'published')
                                <span class="admin-badge badge-success">Published</span>
                            @else
                                <span class="admin-badge badge-warning">Draft</span>
                            @endif
                        </td>
                        <td>
                            <code style="font-size: 0.72rem; color: var(--admin-text-muted);">{{ substr($form->checksum ?? 'N/A', 0, 14) }}...</code>
                        </td>
                        <td style="text-align: end;">
                            <div style="display: inline-flex; align-items: center; gap: 0.4rem;">
                                {{-- Preview Modal Trigger --}}
                                <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="openModal('preview-form-modal-{{ $form->id }}')">
                                    Preview
                                </button>

                                {{-- Edit Draft Trigger (if draft) --}}
                                @if($form->status->value === 'draft')
                                    <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="openDrawer('edit-form-drawer-{{ $form->id }}')">
                                        Edit Schema
                                    </button>

                                    <form method="POST" action="/admin/application-forms/{{ $form->id }}/publish" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-sm admin-btn-success" onclick="return confirm('Lock and publish this form version live?')">
                                            Publish
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
                            No application forms created yet. Click "Create Form Draft" to configure fields for a service.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Drawer: Create New Form Draft --}}
<div class="admin-drawer" id="new-form-drawer">
    <div class="drawer-header">
        <h3 class="drawer-title">Create Form Draft</h3>
        <button type="button" class="close-dialog-btn" onclick="closeDrawer('new-form-drawer')">&times;</button>
    </div>
    <form method="POST" id="new-form-form" style="display: flex; flex-direction: column; flex: 1;">
        @csrf
        <div class="drawer-body">
            <div class="admin-form-group">
                <label class="admin-label" for="select_service_id">Target Service</label>
                <select id="select_service_id" class="admin-select" required onchange="document.getElementById('new-form-form').action = '/admin/application-forms/' + this.value + '/draft';">
                    <option value="">-- Choose Catalog Service --</option>
                    @foreach($services as $s)
                        <option value="{{ $s->id }}">{{ $s->nameEn }} ({{ $s->nameAr }})</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="schema_json_input">Form Schema Definition (JSON)</label>
                <textarea id="schema_json_input" name="schema_json" class="admin-textarea" rows="12" required style="font-family: monospace; font-size: 0.8rem;">{
  "fields": [
    {
      "key": "passport_number",
      "type": "short_text",
      "label_en": "Passport Number",
      "label_ar": "رقم الجواز",
      "order": 1,
      "required": true
    },
    {
      "key": "passport_copy",
      "type": "file",
      "label_en": "Passport Photo Copy",
      "label_ar": "صورة الجواز",
      "order": 2,
      "required": true
    }
  ]
}</textarea>
                <span style="font-size: 0.72rem; color: var(--admin-text-muted); display: block; margin-top: 0.35rem;">
                    Supported types: <code>short_text</code>, <code>long_text</code>, <code>number</code>, <code>date</code>, <code>select</code>, <code>file</code>, <code>image</code>.
                </span>
            </div>
        </div>
        <div class="drawer-footer">
            <button type="button" class="admin-btn admin-btn-ghost" onclick="closeDrawer('new-form-drawer')">Cancel</button>
            <button type="submit" class="admin-btn admin-btn-primary">Save Draft</button>
        </div>
    </form>
</div>

{{-- Per-Form Preview Modals & Edit Drawers --}}
@foreach($forms as $form)
    {{-- Live Preview Modal --}}
    <div class="admin-modal" id="preview-form-modal-{{ $form->id }}">
        <div class="modal-header">
            <h3 class="modal-title">Form Schema Preview (v{{ $form->version }})</h3>
            <button type="button" class="close-dialog-btn" onclick="closeModal('preview-form-modal-{{ $form->id }}')">&times;</button>
        </div>
        <div class="modal-body">
            @if(empty($form->fields))
                <p style="color: var(--admin-text-muted);">No fields defined in this version.</p>
            @else
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @foreach($form->fields as $f)
                        <div class="admin-form-group" style="margin-bottom: 0;">
                            <label class="admin-label">
                                {{ $f->labelEn }} / {{ $f->labelAr }}
                                @if($f->required)
                                    <span style="color: var(--admin-danger);">*</span>
                                @endif
                                <span class="admin-badge badge-neutral" style="font-size: 0.65rem; margin-inline-start: 0.5rem;">{{ $f->type->value }}</span>
                            </label>
                            @if($f->type->value === 'long_text')
                                <textarea class="admin-textarea" rows="2" disabled placeholder="[Textarea input]"></textarea>
                            @elseif($f->type->value === 'file' || $f->type->value === 'image')
                                <input type="file" class="admin-input" disabled>
                            @elseif($f->type->value === 'select')
                                <select class="admin-select" disabled>
                                    <option>Choose option...</option>
                                </select>
                            @else
                                <input type="text" class="admin-input" disabled placeholder="[Sample input: {{ $f->key }}]">
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('preview-form-modal-{{ $form->id }}')">Close Preview</button>
        </div>
    </div>

    {{-- Edit Draft Drawer (if draft) --}}
    @if($form->status->value === 'draft')
        <div class="admin-drawer" id="edit-form-drawer-{{ $form->id }}">
            <div class="drawer-header">
                <h3 class="drawer-title">Edit Draft Schema (v{{ $form->version }})</h3>
                <button type="button" class="close-dialog-btn" onclick="closeDrawer('edit-form-drawer-{{ $form->id }}')">&times;</button>
            </div>
            <form method="POST" action="/admin/application-forms/{{ $form->id }}/draft" style="display: flex; flex-direction: column; flex: 1;">
                @csrf
                @method('PUT')
                <div class="drawer-body">
                    <div class="admin-form-group">
                        <label class="admin-label">Schema JSON Definition</label>
                        <textarea name="schema_json" class="admin-textarea" rows="16" required style="font-family: monospace; font-size: 0.8rem;">{{ json_encode(['fields' => array_map(fn($item) => $item->toArray(), $form->fields)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                    </div>
                </div>
                <div class="drawer-footer">
                    <button type="button" class="admin-btn admin-btn-ghost" onclick="closeDrawer('edit-form-drawer-{{ $form->id }}')">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Update Draft Schema</button>
                </div>
            </form>
        </div>
    @endif
@endforeach
@endsection
