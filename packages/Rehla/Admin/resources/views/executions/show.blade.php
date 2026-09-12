@extends('rehla-admin::layout')

@section('title', 'Execution Inspection')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Execution: {{ $execution->id }}</h2>
        <span class="badge {{ $execution->status === 'completed' ? 'badge-success' : 'badge-warning' }}" style="font-size: 0.9rem;">
            {{ $execution->status }}
        </span>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
        <div><strong>Order ID:</strong> <code>{{ $execution->orderId }}</code></div>
        <div><strong>Account ID:</strong> <code>{{ $execution->accountId }}</code></div>
        <div><strong>Traveler ID:</strong> <code>{{ $execution->travelerId }}</code></div>
        <div><strong>Last Updated:</strong> {{ $execution->lastStatusAt->format('Y-m-d H:i') }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <div class="card">
        <h2>Transition Status</h2>
        <form method="POST" action="/admin/service-executions/{{ $execution->id }}/transition">
            @csrf
            <div class="form-group">
                <label for="target_status">Target Status</label>
                <select id="target_status" name="target_status" required>
                    <option value="">Select target status...</option>
                    <option value="under_review">under_review</option>
                    <option value="in_processing">in_processing</option>
                    <option value="completed">completed</option>
                    <option value="cancelled">cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label for="reason">Transition Note / Reason</label>
                <input type="text" id="reason" name="reason" placeholder="e.g. Processing at embassy">
            </div>
            <button type="submit" class="btn btn-primary">Transition State</button>
        </form>
    </div>

    <div class="card">
        <h2>Request Customer Action</h2>
        <form method="POST" action="/admin/service-executions/{{ $execution->id }}/customer-action">
            @csrf
            <div class="form-group">
                <label for="description_en">Description (English)</label>
                <input type="text" id="description_en" name="description_en" required placeholder="e.g. Passport copy was blurry">
            </div>
            <div class="form-group">
                <label for="description_ar">Description (Arabic)</label>
                <input type="text" id="description_ar" name="description_ar" required placeholder="e.g. صورة الجواز غير واضحة">
            </div>
            <div class="form-group">
                <label for="required_document_purpose">Required Document Purpose</label>
                <input type="text" id="required_document_purpose" name="required_document_purpose" placeholder="e.g. passport_photo">
            </div>
            <div class="form-group">
                <label for="due_in_hours">Due in Hours</label>
                <input type="number" id="due_in_hours" name="due_in_hours" value="48">
            </div>
            <button type="submit" class="btn btn-warning">Issue Action Request</button>
        </form>
    </div>
</div>

<div class="card">
    <h2>Internal Notes</h2>
    <form method="POST" action="/admin/service-executions/{{ $execution->id }}/note" style="margin-bottom: 1.5rem;">
        @csrf
        <div class="form-group">
            <label for="body">Add Note</label>
            <input type="text" id="body" name="body" required placeholder="Internal operational note...">
        </div>
        <button type="submit" class="btn btn-primary" style="font-size: 0.8rem;">Add Internal Note</button>
    </form>

    <h2>Transition History</h2>
    <table>
        <thead>
            <tr>
                <th>Actor</th>
                <th>Transition</th>
                <th>Reason</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            @forelse($execution->history as $h)
                <tr>
                    <td><span class="badge badge-info">{{ $h->actorType }}</span></td>
                    <td><code>{{ $h->fromStatus ?? 'start' }} &rarr; {{ $h->toStatus }}</code></td>
                    <td>{{ $h->reason ?? '—' }}</td>
                    <td>{{ $h->createdAt->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="color: var(--text-muted); text-align: center;">No history entries recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
