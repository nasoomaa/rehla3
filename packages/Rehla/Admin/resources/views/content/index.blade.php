@extends('rehla-admin::layout')

@section('title', 'Content CMS')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Content Management System (CMS)</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Manage bilingual marketing blocks, terms of service, and dynamic guidance text.
            </p>
        </div>
        <div class="admin-card-actions">
            <button type="button" class="admin-btn admin-btn-primary" onclick="openDrawer('new-content-drawer')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Content Block</span>
            </button>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="admin-toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="admin-input" placeholder="Search by content key or title..." data-table-search="content-table">
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
            Total blocks: <strong>{{ count($blocks) }}</strong>
        </div>
    </div>

    {{-- Table --}}
    <div class="admin-table-wrapper">
        <table class="admin-table" id="content-table">
            <thead>
                <tr>
                    <th>Content Key</th>
                    <th>English Title</th>
                    <th>Arabic Title</th>
                    <th>Status</th>
                    <th style="text-align: end;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($blocks as $block)
                    <tr>
                        <td>
                            <code style="font-size: 0.85rem; font-weight: 700; color: var(--admin-primary); background: var(--admin-bg-surface-elevated); padding: 0.2rem 0.5rem; border-radius: var(--radius-xs); border: 1px solid var(--admin-border-subtle);">
                                {{ $block->key }}
                            </code>
                        </td>
                        <td><strong style="color: var(--admin-text-primary);">{{ $block->titleEn }}</strong></td>
                        <td style="color: var(--admin-text-secondary);">{{ $block->titleAr }}</td>
                        <td>
                            @if($block->status->value === 'published')
                                <span class="admin-badge badge-success">Published</span>
                            @else
                                <span class="admin-badge badge-warning">Draft</span>
                            @endif
                        </td>
                        <td style="text-align: end;">
                            <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="openModal('view-content-modal-{{ md5($block->key) }}')">
                                View Content
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">
                            No content blocks created yet. Click "Add Content Block" to add one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Slide-over Drawer: Create Content Block --}}
<div class="admin-drawer" id="new-content-drawer">
    <div class="drawer-header">
        <h3 class="drawer-title">Create Content Block</h3>
        <button type="button" class="close-dialog-btn" onclick="closeDrawer('new-content-drawer')">&times;</button>
    </div>
    <form method="POST" action="/admin/content" style="display: flex; flex-direction: column; flex: 1;">
        @csrf
        <div class="drawer-body">
            <div class="admin-form-group">
                <label class="admin-label" for="content_key">Content Identifier Key</label>
                <input type="text" id="content_key" name="key" class="admin-input" required placeholder="e.g. visa.uae_guidelines">
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="title_en">Title (English)</label>
                <input type="text" id="title_en" name="title_en" class="admin-input" required>
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="title_ar">Title (Arabic)</label>
                <input type="text" id="title_ar" name="title_ar" class="admin-input" required>
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="body_en">Body Content (English)</label>
                <textarea id="body_en" name="body_en" class="admin-textarea" rows="4" placeholder="Markdown or plain text..."></textarea>
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="body_ar">Body Content (Arabic)</label>
                <textarea id="body_ar" name="body_ar" class="admin-textarea" rows="4" placeholder="نص عادي أو بصيغة Markdown..."></textarea>
            </div>
        </div>
        <div class="drawer-footer">
            <button type="button" class="admin-btn admin-btn-ghost" onclick="closeDrawer('new-content-drawer')">Cancel</button>
            <button type="submit" class="admin-btn admin-btn-primary">Save Content Block</button>
        </div>
    </form>
</div>

{{-- Per-Block View Modals --}}
@foreach($blocks as $block)
    <div class="admin-modal" id="view-content-modal-{{ md5($block->key) }}">
        <div class="modal-header">
            <h3 class="modal-title">{{ $block->key }}</h3>
            <button type="button" class="close-dialog-btn" onclick="closeModal('view-content-modal-{{ md5($block->key) }}')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 1.25rem;">
                <div style="font-size: 0.75rem; color: var(--admin-text-muted); text-transform: uppercase; font-weight: 600;">English Content</div>
                <div style="font-size: 1rem; font-weight: 700; color: var(--admin-text-primary); margin: 0.25rem 0;">{{ $block->titleEn }}</div>
                <div style="font-size: 0.85rem; color: var(--admin-text-secondary); background: var(--admin-bg-surface-elevated); padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--admin-border-subtle); white-space: pre-wrap;">{{ $block->bodyEn ?? 'No English body text.' }}</div>
            </div>

            <div>
                <div style="font-size: 0.75rem; color: var(--admin-text-muted); text-transform: uppercase; font-weight: 600;">Arabic Content</div>
                <div style="font-size: 1rem; font-weight: 700; color: var(--admin-text-primary); margin: 0.25rem 0;">{{ $block->titleAr }}</div>
                <div style="font-size: 0.85rem; color: var(--admin-text-secondary); background: var(--admin-bg-surface-elevated); padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--admin-border-subtle); white-space: pre-wrap;">{{ $block->bodyAr ?? 'لا يوجد نص بالعربية.' }}</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('view-content-modal-{{ md5($block->key) }}')">Close</button>
        </div>
    </div>
@endforeach
@endsection
