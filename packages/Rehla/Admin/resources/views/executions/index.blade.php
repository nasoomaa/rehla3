@extends('rehla-admin::layout')

@section('title', 'Service Executions')

@section('content')
<div class="card">
    <h2>Service Fulfillment Executions</h2>
    <table>
        <thead>
            <tr>
                <th>Execution ID</th>
                <th>Order ID</th>
                <th>Status</th>
                <th>Last Status At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($executions as $exec)
                <tr>
                    <td><code>{{ $exec->id }}</code></td>
                    <td><code>{{ $exec->orderId }}</code></td>
                    <td>
                        <span class="badge {{ $exec->status === 'completed' ? 'badge-success' : ($exec->status === 'cancelled' ? 'badge-danger' : 'badge-warning') }}">
                            {{ $exec->status }}
                        </span>
                    </td>
                    <td>{{ $exec->lastStatusAt->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="/admin/service-executions/{{ $exec->id }}" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Inspect</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="color: var(--text-muted); text-align: center;">No service executions in progress.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
