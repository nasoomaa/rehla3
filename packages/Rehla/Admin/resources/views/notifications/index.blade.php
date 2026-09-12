@extends('rehla-admin::layout')

@section('title', 'System Notifications')

@section('content')
<div class="card">
    <h2>Dispatched In-App & Outbox Notifications</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Recipient User ID</th>
                <th>Type</th>
                <th>Channel</th>
                <th>Read Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($notifications as $notif)
                <tr>
                    <td><code>{{ $notif->id }}</code></td>
                    <td><code>{{ $notif->userId }}</code></td>
                    <td><span class="badge badge-info">{{ $notif->type }}</span></td>
                    <td>{{ $notif->channel }}</td>
                    <td>
                        <span class="badge {{ $notif->readAt !== null ? 'badge-success' : 'badge-warning' }}">
                            {{ $notif->readAt !== null ? 'Read' : 'Unread' }}
                        </span>
                    </td>
                    <td>{{ $notif->createdAt?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="color: var(--text-muted); text-align: center;">No notifications dispatched.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
