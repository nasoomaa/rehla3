@php
    $currentRoute = request()->path();
    $user = Auth::guard('admin')->user();
    $actor = $user instanceof \Rehla\Identity\Models\User ? $user->toActorData() : null;
    $abilities = $actor?->abilities ?? [];
    $isSuperAdmin = in_array('super_admin', $abilities, true) || in_array('roles.manage', $abilities, true);
@endphp

<aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-brand">
        <a href="/admin/overview" class="brand-logo">
            <div class="logo-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 19 21 12 17 5 21 12 2"></polygon>
                </svg>
            </div>
            <div class="brand-text">
                <span class="brand-title">REHLA</span>
                <span class="brand-subtitle">Console</span>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn" id="sidebar-close-btn" aria-label="Close Sidebar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="admin-nav">
        {{-- Section 1: Analytics & Monitoring --}}
        <div class="nav-section-title">ANALYTICS & MONITORING</div>
        <a href="/admin/overview" class="nav-link {{ str_starts_with($currentRoute, 'admin/overview') || $currentRoute === 'admin' ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
            </svg>
            <span>Overview</span>
        </a>
        <a href="/admin/audit-log" class="nav-link {{ str_starts_with($currentRoute, 'admin/audit-log') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/>
            </svg>
            <span>Audit Trail</span>
        </a>

        {{-- Section 2: Catalog & Operations --}}
        <div class="nav-section-title">CATALOG & OPERATIONS</div>
        <a href="/admin/services" class="nav-link {{ str_starts_with($currentRoute, 'admin/services') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>
            </svg>
            <span>Services Catalog</span>
        </a>
        <a href="/admin/application-forms" class="nav-link {{ str_starts_with($currentRoute, 'admin/application-forms') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M9 14h6"/><path d="M9 18h6"/><path d="M9 10h1"/>
            </svg>
            <span>Application Forms</span>
        </a>
        <a href="/admin/orders" class="nav-link {{ str_starts_with($currentRoute, 'admin/orders') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
            </svg>
            <span>Orders</span>
        </a>
        <a href="/admin/service-executions" class="nav-link {{ str_starts_with($currentRoute, 'admin/service-executions') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>
            </svg>
            <span>Executions</span>
        </a>

        {{-- Section 3: Finance & Treasury --}}
        <div class="nav-section-title">FINANCE & TREASURY</div>
        <a href="/admin/top-up-requests" class="nav-link {{ str_starts_with($currentRoute, 'admin/top-up-requests') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            <span>Top-up Requests</span>
        </a>
        <a href="/admin/bank-accounts" class="nav-link {{ str_starts_with($currentRoute, 'admin/bank-accounts') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m2 7 10-5 10 5"/><path d="M2 17h20"/><path d="M6 10v7"/><path d="M10 10v7"/><path d="M14 10v7"/><path d="M18 10v7"/><path d="M2 21h20"/>
            </svg>
            <span>Bank Accounts</span>
        </a>
        <a href="/admin/wallets" class="nav-link {{ str_starts_with($currentRoute, 'admin/wallets') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>
            </svg>
            <span>Wallets & Ledger</span>
        </a>

        {{-- Section 4: CRM & Travelers --}}
        <div class="nav-section-title">CRM & TRAVELERS</div>
        <a href="/admin/customers" class="nav-link {{ str_starts_with($currentRoute, 'admin/customers') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span>Customers</span>
        </a>
        <a href="/admin/travelers" class="nav-link {{ str_starts_with($currentRoute, 'admin/travelers') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 18a4 4 0 0 0 4 4h12a4 4 0 0 0 4-4V7a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v11Z"/><path d="M9 3v4"/><path d="M15 3v4"/><path d="M2 10h20"/>
            </svg>
            <span>Travelers CRM</span>
        </a>

        {{-- Section 5: Governance & System --}}
        <div class="nav-section-title">GOVERNANCE & SYSTEM</div>
        <a href="/admin/content" class="nav-link {{ str_starts_with($currentRoute, 'admin/content') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/>
            </svg>
            <span>Content CMS</span>
        </a>
        <a href="/admin/notifications" class="nav-link {{ str_starts_with($currentRoute, 'admin/notifications') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
            </svg>
            <span>Notifications</span>
        </a>
        <a href="/admin/roles-permissions" class="nav-link {{ str_starts_with($currentRoute, 'admin/roles-permissions') ? 'active' : '' }}">
            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <span>Roles & Access</span>
        </a>
    </nav>

    <div class="admin-user-footer">
        <div class="user-info">
            <div class="user-avatar">
                {{ strtoupper(substr($user->name ?? 'A', 0, 1)) }}
            </div>
            <div class="user-details">
                <div class="user-name">{{ $user->name ?? 'Staff User' }}</div>
                <div class="user-role">{{ $isSuperAdmin ? 'Super Administrator' : 'Staff Officer' }}</div>
            </div>
        </div>
        <form method="POST" action="/admin/logout" class="logout-form">
            @csrf
            <button type="submit" class="logout-btn" title="Sign Out">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>
                </svg>
            </button>
        </form>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
