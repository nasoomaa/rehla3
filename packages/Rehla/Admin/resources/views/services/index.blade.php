@extends('rehla-admin::layout')

@section('title', 'Service Catalog Management')

@section('content')
<div class="card">
    <h2>Add New Service</h2>
    <form method="POST" action="/admin/services">
        @csrf
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="name_en">Service Name (English)</label>
                <input type="text" id="name_en" name="name_en" required placeholder="e.g. UAE Tourist Visa">
            </div>
            <div class="form-group">
                <label for="name_ar">Service Name (Arabic)</label>
                <input type="text" id="name_ar" name="name_ar" required placeholder="e.g. تأشيرة سياحة الإمارات">
            </div>
            <div class="form-group">
                <label for="short_description_en">Short Description (EN)</label>
                <input type="text" id="short_description_en" name="short_description_en" required>
            </div>
            <div class="form-group">
                <label for="short_description_ar">Short Description (AR)</label>
                <input type="text" id="short_description_ar" name="short_description_ar" required>
            </div>
            <div class="form-group">
                <label for="detailed_description_en">Detailed Description (EN)</label>
                <textarea id="detailed_description_en" name="detailed_description_en" rows="2" required></textarea>
            </div>
            <div class="form-group">
                <label for="detailed_description_ar">Detailed Description (AR)</label>
                <textarea id="detailed_description_ar" name="detailed_description_ar" rows="2" required></textarea>
            </div>
            <div class="form-group">
                <label for="expected_duration_en">Expected Duration (EN)</label>
                <input type="text" id="expected_duration_en" name="expected_duration_en" required placeholder="2-3 days">
            </div>
            <div class="form-group">
                <label for="expected_duration_ar">Expected Duration (AR)</label>
                <input type="text" id="expected_duration_ar" name="expected_duration_ar" required placeholder="٢-٣ أيام">
            </div>
            <div class="form-group">
                <label for="price_minor">Price Minor Units (SDG)</label>
                <input type="number" id="price_minor" name="price_minor" required placeholder="5000000 = 50,000 SDG">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">Create Service Draft</button>
    </form>
</div>

<div class="card">
    <h2>Registered Services</h2>
    <table>
        <thead>
            <tr>
                <th>Service Name</th>
                <th>Status</th>
                <th>Price (SDG)</th>
                <th>Duration</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($services as $srv)
                <tr>
                    <td>
                        <strong>{{ $srv->nameEn }}</strong><br>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">{{ $srv->nameAr }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $srv->status->value === 'published' ? 'badge-success' : 'badge-warning' }}">
                            {{ $srv->status->value }}
                        </span>
                    </td>
                    <td>{{ number_format($srv->currentPriceMinor / 100, 2) }}</td>
                    <td>{{ $srv->expectedDurationEn }}</td>
                    <td>
                        @if($srv->status->value === 'draft')
                            <form method="POST" action="/admin/services/{{ $srv->id }}/publish" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-success" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Publish</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="color: var(--text-muted); text-align: center;">No services found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
