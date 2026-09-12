@extends('rehla-admin::layout')

@section('title', 'Application Forms Management')

@section('content')
<div class="card">
    <h2>Form Version Management</h2>
    <table>
        <thead>
            <tr>
                <th>Service ID</th>
                <th>Version</th>
                <th>Fields Count</th>
                <th>Status</th>
                <th>Checksum</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($forms as $form)
                <tr>
                    <td><code>{{ $form->serviceId }}</code></td>
                    <td>v{{ $form->version }}</td>
                    <td>{{ count($form->fields) }} fields</td>
                    <td>
                        <span class="badge {{ $form->status->value === 'published' ? 'badge-success' : 'badge-warning' }}">
                            {{ $form->status->value }}
                        </span>
                    </td>
                    <td><code style="font-size: 0.75rem;">{{ substr($form->checksum ?? 'N/A', 0, 16) }}...</code></td>
                    <td>
                        @if($form->status->value === 'draft')
                            <form method="POST" action="/admin/application-forms/{{ $form->id }}/publish" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-success" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Publish Schema</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="color: var(--text-muted); text-align: center;">No application form versions found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
