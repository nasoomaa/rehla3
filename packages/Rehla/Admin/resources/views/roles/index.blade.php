@extends('rehla-admin::layout')

@section('title', 'Roles & Permissions')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h1 class="admin-card-title">Roles & Access Governance</h1>
            <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Granular capability matrix and staff role assignment enforcing deny-by-default access.
            </p>
        </div>
        <div class="admin-card-actions">
            <button type="button" class="admin-btn admin-btn-primary" onclick="openDrawer('assign-role-drawer')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>
                </svg>
                <span>Assign Staff Role</span>
            </button>
        </div>
    </div>

    {{-- Role Capability Cards Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
        @foreach($roles as $role)
            <div style="background: var(--admin-bg-surface-elevated); border: 1px solid var(--admin-border-subtle); border-radius: var(--radius-md); padding: 1.25rem; display: flex; flex-direction: column;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <div>
                        <span style="font-weight: 700; font-size: 1.05rem; color: var(--admin-text-primary);">{{ $role->label ?? ucfirst($role->name) }}</span>
                        <code style="font-size: 0.72rem; color: var(--admin-text-muted); display: block;">{{ $role->name }}</code>
                    </div>
                    <span class="admin-badge badge-info">{{ count($role->abilities) }} Abilities</span>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: auto; padding-top: 0.75rem; border-top: 1px solid var(--admin-border-subtle);">
                    @foreach($role->abilities as $ability)
                        @php
                            $isSensitive = in_array($ability, ['topups.review', 'roles.manage', 'audit.view'], true);
                        @endphp
                        <span class="admin-badge {{ $isSensitive ? 'badge-warning' : 'badge-neutral' }}" title="{{ $isSensitive ? 'Sensitive: Requires fresh TOTP MFA within 12h' : 'Standard capability' }}" style="font-size: 0.68rem;">
                            @if($isSensitive)
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            @endif
                            {{ $ability }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Staff Users Role Directory --}}
    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 1rem; color: var(--admin-text-primary);">
        Staff Members & Role Membership
    </h3>

    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Account Status</th>
                    <th>Role Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffUsers as $u)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--admin-text-primary);">{{ $u->name }}</div>
                            <code style="font-size: 0.72rem; color: var(--admin-text-muted);">{{ substr($u->id, 0, 13) }}...</code>
                        </td>
                        <td style="color: var(--admin-text-secondary);">{{ $u->email }}</td>
                        <td>
                            <span class="admin-badge {{ $u->status === 'active' ? 'badge-success' : 'badge-warning' }}">
                                {{ $u->status }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                @foreach($roles as $r)
                                    <form method="POST" action="/admin/roles-permissions/assign" style="display: inline;">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $u->id }}">
                                        <input type="hidden" name="role" value="{{ $r->name }}">
                                        <button type="submit" class="admin-btn admin-btn-sm admin-btn-secondary" title="Assign {{ $r->name }} role">
                                            + {{ $r->name }}
                                        </button>
                                    </form>

                                    <form method="POST" action="/admin/roles-permissions/revoke" style="display: inline;">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $u->id }}">
                                        <input type="hidden" name="role" value="{{ $r->name }}">
                                        <button type="submit" class="admin-btn admin-btn-sm admin-btn-ghost" style="color: var(--admin-danger);" title="Revoke {{ $r->name }} role">
                                            &times;
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--admin-text-muted); padding: 2rem;">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Slide-over Drawer: Assign Staff Role --}}
<div class="admin-drawer" id="assign-role-drawer">
    <div class="drawer-header">
        <h3 class="drawer-title">Assign Role to Staff Member</h3>
        <button type="button" class="close-dialog-btn" onclick="closeDrawer('assign-role-drawer')">&times;</button>
    </div>
    <form method="POST" action="/admin/roles-permissions/assign" style="display: flex; flex-direction: column; flex: 1;">
        @csrf
        <div class="drawer-body">
            <div class="admin-form-group">
                <label class="admin-label" for="select_staff_user">Select User</label>
                <select id="select_staff_user" name="user_id" class="admin-select" required>
                    <option value="">-- Choose User --</option>
                    @foreach($staffUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-form-group">
                <label class="admin-label" for="select_role_name">Select Role</label>
                <select id="select_role_name" name="role" class="admin-select" required>
                    @foreach($roles as $r)
                        <option value="{{ $r->name }}">{{ $r->label ?? ucfirst($r->name) }} ({{ $r->name }})</option>
                    @endforeach
                </select>
            </div>
            <div style="padding: 0.85rem; background: var(--admin-warning-light); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: var(--radius-sm); margin-top: 1rem;">
                <div style="font-size: 0.78rem; color: var(--admin-warning); font-weight: 600;">
                    Security Note
                </div>
                <div style="font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 0.25rem;">
                    Staff members require fresh MFA confirmation within 12 hours to perform sensitive operations (topup approvals, role management, audit inspections).
                </div>
            </div>
        </div>
        <div class="drawer-footer">
            <button type="button" class="admin-btn admin-btn-ghost" onclick="closeDrawer('assign-role-drawer')">Cancel</button>
            <button type="submit" class="admin-btn admin-btn-primary">Assign Role</button>
        </div>
    </form>
</div>
@endsection
