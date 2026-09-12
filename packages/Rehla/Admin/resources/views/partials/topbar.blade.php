@php
    $user = Auth::guard('admin')->user();
    $mfaAt = $user?->staffProfile?->mfa_confirmed_at;
    $isMfaFresh = $mfaAt !== null && \Carbon\CarbonImmutable::parse($mfaAt)->isAfter(\Carbon\CarbonImmutable::now()->subHours(12));
@endphp

<header class="admin-topbar">
    <div class="topbar-start">
        <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle-btn" aria-label="Toggle Menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/>
            </svg>
        </button>
        <div class="topbar-breadcrumbs">
            <span class="crumb-root">Rehla</span>
            <span class="crumb-separator">/</span>
            <span class="crumb-current">@yield('title', 'Console')</span>
        </div>
    </div>

    <div class="topbar-actions">
        {{-- MFA Status Indicator --}}
        @if($isMfaFresh)
            <div class="mfa-badge mfa-verified" title="TOTP MFA verified within 12 hours">
                <span class="pulse-dot"></span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>MFA Active</span>
            </div>
        @else
            <a href="/admin/mfa" class="mfa-badge mfa-required" title="MFA confirmation required for sensitive actions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                <span>Confirm MFA</span>
            </a>
        @endif

        {{-- Theme Switcher (Dark / Light Mode) --}}
        <button type="button" class="theme-toggle-btn" id="rehla-admin-theme-toggle" aria-label="Toggle Dark / Light Theme" title="Toggle Theme">
            <span class="theme-icon-dark">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                </svg>
            </span>
            <span class="theme-icon-light">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                </svg>
            </span>
        </button>

        {{-- Quick User Pill --}}
        <div class="topbar-user-pill">
            <div class="pill-avatar">{{ strtoupper(substr($user->name ?? 'A', 0, 1)) }}</div>
            <span class="pill-name">{{ $user->name ?? 'Admin' }}</span>
        </div>
    </div>
</header>
