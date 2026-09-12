@extends('rehla-admin::layout')

@section('title', 'Content Management')

@section('content')
<div class="card">
    <h2>Create Content Block</h2>
    <form method="POST" action="/admin/content">
        @csrf
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="key">Content Key</label>
                <input type="text" id="key" name="key" required placeholder="e.g. faq.passport_rules">
            </div>
            <div></div>
            <div class="form-group">
                <label for="title_en">Title (English)</label>
                <input type="text" id="title_en" name="title_en" required>
            </div>
            <div class="form-group">
                <label for="title_ar">Title (Arabic)</label>
                <input type="text" id="title_ar" name="title_ar" required>
            </div>
            <div class="form-group">
                <label for="body_en">Body (English)</label>
                <textarea id="body_en" name="body_en" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="body_ar">Body (Arabic)</label>
                <textarea id="body_ar" name="body_ar" rows="3"></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">Save Content Block</button>
    </form>
</div>

<div class="card">
    <h2>Managed Content Blocks</h2>
    <table>
        <thead>
            <tr>
                <th>Key</th>
                <th>Title (EN)</th>
                <th>Title (AR)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($blocks as $block)
                <tr>
                    <td><code>{{ $block->key }}</code></td>
                    <td><strong>{{ $block->titleEn }}</strong></td>
                    <td>{{ $block->titleAr }}</td>
                    <td><span class="badge {{ $block->status->value === 'published' ? 'badge-success' : 'badge-warning' }}">{{ $block->status->value }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="color: var(--text-muted); text-align: center;">No content blocks found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
