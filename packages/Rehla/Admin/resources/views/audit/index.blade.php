@extends('rehla-admin::layout')

@section('title', 'System Audit Trail (Read-Only)')

@section('content')
<div class="card">
    <h2>Immutable Audit Entries</h2>
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Actor Type</th>
                <th>Action</th>
                <th>Subject</th>
                <th>Metadata</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->occurredAt?->format('Y-m-d H:i:s') ?? 'N/A' }}</td>
                    <td><span class="badge badge-info">{{ $entry->actorType }}</span></td>
                    <td><strong>{{ $entry->action }}</strong></td>
                    <td><code>{{ $entry->subjectType }}:{{ $entry->subjectId ?? '*' }}</code></td>
                    <td><code style="font-size: 0.75rem;">{{ json_encode($entry->metadata) }}</code></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="color: var(--text-muted); text-align: center;">No audit entries found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
