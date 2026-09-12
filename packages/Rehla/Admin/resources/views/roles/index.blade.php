@extends('rehla-admin::layout')

@section('title', 'Staff Roles & Capability Matrix')

@section('content')
<div class="card">
    <h2>Configured Administrative Roles</h2>
    <table>
        <thead>
            <tr>
                <th>Role Name</th>
                <th>Label</th>
                <th>Assigned Abilities / Capabilities</th>
            </tr>
        </thead>
        <tbody>
            @forelse($roles as $role)
                <tr>
                    <td><strong style="font-family: monospace;">{{ $role->name }}</strong></td>
                    <td>{{ $role->label ?? 'N/A' }}</td>
                    <td>
                        @foreach($role->abilities as $ability)
                            <span class="badge badge-info" style="margin-inline-end: 0.25rem;">{{ $ability }}</span>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="color: var(--text-muted); text-align: center;">No administrative roles defined.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
