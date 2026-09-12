<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Console') — Rehla Admin</title>

    {{-- Early theme detection to prevent flash of unstyled content --}}
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('rehla_admin_theme');
                if (savedTheme === 'light' || savedTheme === 'dark') {
                    document.documentElement.setAttribute('data-theme', savedTheme);
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                    document.documentElement.setAttribute('data-theme', 'light');
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>

    {{-- Typography --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">

    <style>
        /* ==========================================================================
           REHLA ADMIN DESIGN SYSTEM TOKENS
           ========================================================================== */
        :root {
            --font-sans: 'Plus Jakarta Sans', 'Tajawal', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --radius-xs: 4px;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --radius-full: 9999px;
            --transition-smooth: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Dark Theme (Default) */
        html[data-theme="dark"] {
            --admin-bg-canvas: #070b19;
            --admin-bg-surface: #0e172f;
            --admin-bg-surface-elevated: #142040;
            --admin-bg-surface-hover: #1b2b54;
            --admin-border-subtle: rgba(255, 255, 255, 0.08);
            --admin-border-strong: rgba(255, 255, 255, 0.16);
            --admin-border-focus: #3b82f6;

            --admin-text-primary: #f8fafc;
            --admin-text-secondary: #cbd5e1;
            --admin-text-muted: #94a3b8;
            --admin-text-faint: #64748b;

            --admin-primary: #3b82f6;
            --admin-primary-hover: #2563eb;
            --admin-primary-light: rgba(59, 130, 246, 0.15);

            --admin-success: #10b981;
            --admin-success-light: rgba(16, 185, 129, 0.15);

            --admin-danger: #ef4444;
            --admin-danger-light: rgba(239, 68, 68, 0.15);

            --admin-warning: #f59e0b;
            --admin-warning-light: rgba(245, 158, 11, 0.15);

            --admin-info: #06b6d4;
            --admin-info-light: rgba(6, 182, 212, 0.15);

            --admin-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.3);
            --admin-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.4);
            --admin-shadow-lg: 0 12px 32px rgba(0, 0, 0, 0.5);

            --admin-backdrop: rgba(3, 7, 18, 0.75);
        }

        /* Light Theme */
        html[data-theme="light"] {
            --admin-bg-canvas: #f4f6fb;
            --admin-bg-surface: #ffffff;
            --admin-bg-surface-elevated: #f8fafc;
            --admin-bg-surface-hover: #f1f5f9;
            --admin-border-subtle: #e2e8f0;
            --admin-border-strong: #cbd5e1;
            --admin-border-focus: #2563eb;

            --admin-text-primary: #0f172a;
            --admin-text-secondary: #334155;
            --admin-text-muted: #64748b;
            --admin-text-faint: #94a3b8;

            --admin-primary: #2563eb;
            --admin-primary-hover: #1d4ed8;
            --admin-primary-light: rgba(37, 99, 235, 0.08);

            --admin-success: #059669;
            --admin-success-light: rgba(5, 150, 105, 0.1);

            --admin-danger: #dc2626;
            --admin-danger-light: rgba(220, 38, 38, 0.1);

            --admin-warning: #d97706;
            --admin-warning-light: rgba(217, 119, 6, 0.1);

            --admin-info: #0891b2;
            --admin-info-light: rgba(8, 145, 178, 0.1);

            --admin-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
            --admin-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);
            --admin-shadow-lg: 0 16px 36px rgba(0, 0, 0, 0.08);

            --admin-backdrop: rgba(15, 23, 42, 0.45);
        }

        /* ==========================================================================
           RESET & CORE LAYOUT
           ========================================================================== */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background: var(--admin-bg-canvas);
            color: var(--admin-text-primary);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* ==========================================================================
           SIDEBAR STYLES
           ========================================================================== */
        .admin-sidebar {
            width: 270px;
            background: var(--admin-bg-surface);
            border-inline-end: 1px solid var(--admin-border-subtle);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 100;
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .admin-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--admin-border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--admin-text-primary);
        }

        .logo-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        .brand-title {
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: 0.08em;
            background: linear-gradient(135deg, #60a5fa, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        html[data-theme="light"] .brand-title {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-subtitle {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--admin-text-muted);
            display: block;
            margin-top: -2px;
        }

        .sidebar-close-btn {
            display: none;
            background: transparent;
            border: none;
            color: var(--admin-text-muted);
            cursor: pointer;
            padding: 0.25rem;
        }

        .admin-nav {
            padding: 1.25rem 0.85rem;
            flex: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--admin-border-subtle) transparent;
        }

        .nav-section-title {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--admin-text-faint);
            margin: 1.2rem 0.75rem 0.5rem;
        }

        .nav-section-title:first-child {
            margin-top: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.85rem;
            border-radius: var(--radius-sm);
            color: var(--admin-text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.2rem;
            transition: var(--transition-smooth);
        }

        .nav-link:hover {
            background: var(--admin-bg-surface-elevated);
            color: var(--admin-text-primary);
        }

        .nav-link.active {
            background: var(--admin-primary-light);
            color: var(--admin-primary);
            font-weight: 600;
        }

        .nav-link .nav-icon {
            flex-shrink: 0;
            color: inherit;
        }

        .admin-user-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--admin-border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            background: var(--admin-bg-surface);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            overflow: hidden;
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: var(--radius-full);
            background: var(--admin-primary-light);
            color: var(--admin-primary);
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid var(--admin-border-subtle);
        }

        .user-details {
            overflow: hidden;
        }

        .user-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--admin-text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.68rem;
            color: var(--admin-text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .logout-btn {
            background: transparent;
            border: none;
            color: var(--admin-text-muted);
            cursor: pointer;
            padding: 0.4rem;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-smooth);
        }

        .logout-btn:hover {
            color: var(--admin-danger);
            background: var(--admin-danger-light);
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--admin-backdrop);
            backdrop-filter: blur(4px);
            z-index: 90;
        }

        /* ==========================================================================
           MAIN CONTENT WRAPPER & TOPBAR
           ========================================================================== */
        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-topbar {
            background: var(--admin-bg-surface);
            border-bottom: 1px solid var(--admin-border-subtle);
            padding: 0.85rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(8px);
        }

        .topbar-start {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-toggle-btn {
            display: none;
            background: transparent;
            border: none;
            color: var(--admin-text-muted);
            cursor: pointer;
            padding: 0.35rem;
            border-radius: var(--radius-sm);
        }

        .sidebar-toggle-btn:hover {
            background: var(--admin-bg-surface-elevated);
            color: var(--admin-text-primary);
        }

        .topbar-breadcrumbs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .crumb-root {
            color: var(--admin-text-muted);
            font-weight: 500;
        }

        .crumb-separator {
            color: var(--admin-text-faint);
            font-size: 0.75rem;
        }

        .crumb-current {
            color: var(--admin-text-primary);
            font-weight: 600;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .mfa-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition-smooth);
        }

        .mfa-verified {
            background: var(--admin-success-light);
            color: var(--admin-success);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .mfa-required {
            background: var(--admin-warning-light);
            color: var(--admin-warning);
            border: 1px solid rgba(245, 158, 11, 0.25);
            cursor: pointer;
        }

        .mfa-required:hover {
            filter: brightness(1.1);
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--admin-success);
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .theme-toggle-btn {
            background: var(--admin-bg-surface-elevated);
            border: 1px solid var(--admin-border-subtle);
            color: var(--admin-text-muted);
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-smooth);
        }

        .theme-toggle-btn:hover {
            color: var(--admin-text-primary);
            border-color: var(--admin-border-strong);
        }

        html[data-theme="dark"] .theme-icon-dark { display: none; }
        html[data-theme="dark"] .theme-icon-light { display: block; }
        html[data-theme="light"] .theme-icon-dark { display: block; }
        html[data-theme="light"] .theme-icon-light { display: none; }

        .topbar-user-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.65rem 0.25rem 0.35rem;
            background: var(--admin-bg-surface-elevated);
            border: 1px solid var(--admin-border-subtle);
            border-radius: var(--radius-full);
        }

        .pill-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--admin-primary);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pill-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--admin-text-primary);
        }

        .admin-content {
            padding: 2rem;
            flex: 1;
        }

        /* ==========================================================================
           REUSABLE UI COMPONENTS
           ========================================================================== */
        .admin-card {
            background: var(--admin-bg-surface);
            border: 1px solid var(--admin-border-subtle);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--admin-shadow-sm);
        }

        .admin-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .admin-card-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .admin-card-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Grid Stats */
        .admin-grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--admin-bg-surface);
            border: 1px solid var(--admin-border-subtle);
            border-radius: var(--radius-lg);
            padding: 1.35rem;
            box-shadow: var(--admin-shadow-sm);
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--admin-border-strong);
            box-shadow: var(--admin-shadow-md);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--admin-primary), transparent);
            opacity: 0.5;
        }

        .stat-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--admin-text-muted);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--admin-text-primary);
            line-height: 1.1;
        }

        /* Table Components */
        .admin-table-wrapper {
            overflow-x: auto;
            border: 1px solid var(--admin-border-subtle);
            border-radius: var(--radius-md);
        }

        table.admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: start;
        }

        table.admin-table th, table.admin-table td {
            padding: 0.85rem 1.15rem;
            border-bottom: 1px solid var(--admin-border-subtle);
            font-size: 0.875rem;
        }

        table.admin-table th {
            background: var(--admin-bg-surface-elevated);
            color: var(--admin-text-muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        table.admin-table tr:hover td {
            background: var(--admin-bg-surface-hover);
        }

        table.admin-table tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: var(--radius-full);
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .badge-success { background: var(--admin-success-light); color: var(--admin-success); }
        .badge-danger { background: var(--admin-danger-light); color: var(--admin-danger); }
        .badge-warning { background: var(--admin-warning-light); color: var(--admin-warning); }
        .badge-info { background: var(--admin-info-light); color: var(--admin-info); }
        .badge-neutral { background: var(--admin-bg-surface-elevated); color: var(--admin-text-muted); }

        /* Buttons */
        .admin-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.55rem 1.1rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: var(--transition-smooth);
            white-space: nowrap;
        }

        .admin-btn-sm {
            padding: 0.35rem 0.7rem;
            font-size: 0.75rem;
        }

        .admin-btn-primary {
            background: var(--admin-primary);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }

        .admin-btn-primary:hover {
            background: var(--admin-primary-hover);
            transform: translateY(-1px);
        }

        .admin-btn-secondary {
            background: var(--admin-bg-surface-elevated);
            color: var(--admin-text-primary);
            border-color: var(--admin-border-subtle);
        }

        .admin-btn-secondary:hover {
            background: var(--admin-bg-surface-hover);
            border-color: var(--admin-border-strong);
        }

        .admin-btn-success {
            background: var(--admin-success);
            color: #ffffff;
        }

        .admin-btn-success:hover {
            filter: brightness(1.1);
        }

        .admin-btn-danger {
            background: var(--admin-danger);
            color: #ffffff;
        }

        .admin-btn-danger:hover {
            filter: brightness(1.1);
        }

        .admin-btn-ghost {
            background: transparent;
            color: var(--admin-text-muted);
        }

        .admin-btn-ghost:hover {
            background: var(--admin-bg-surface-elevated);
            color: var(--admin-text-primary);
        }

        /* Forms & Inputs */
        .admin-form-group {
            margin-bottom: 1.15rem;
        }

        .admin-label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--admin-text-secondary);
        }

        .admin-input, .admin-select, .admin-textarea {
            width: 100%;
            padding: 0.65rem 0.85rem;
            background: var(--admin-bg-canvas);
            border: 1px solid var(--admin-border-subtle);
            border-radius: var(--radius-sm);
            color: var(--admin-text-primary);
            font-size: 0.875rem;
            font-family: inherit;
            transition: var(--transition-smooth);
        }

        .admin-input:focus, .admin-select:focus, .admin-textarea:focus {
            outline: none;
            border-color: var(--admin-border-focus);
            box-shadow: 0 0 0 3px var(--admin-primary-light);
        }

        /* Table Toolbar Filter */
        .admin-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            min-width: 260px;
        }

        .search-box input {
            padding-inline-start: 2.25rem;
        }

        .search-icon {
            position: absolute;
            top: 50%;
            inset-inline-start: 0.75rem;
            transform: translateY(-50%);
            color: var(--admin-text-muted);
            pointer-events: none;
        }

        /* Toast Notifications */
        .admin-toast {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            box-shadow: var(--admin-shadow-sm);
        }

        .toast-success {
            background: var(--admin-success-light);
            border: 1px solid var(--admin-success);
            color: var(--admin-success);
        }

        .toast-danger {
            background: var(--admin-danger-light);
            border: 1px solid var(--admin-danger);
            color: var(--admin-danger);
        }

        .toast-message { flex: 1; }
        .toast-close {
            background: transparent;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.25rem;
            line-height: 1;
        }

        /* ==========================================================================
           MODALS & SLIDING DRAWERS
           ========================================================================== */
        .admin-modal-backdrop, .admin-drawer-backdrop {
            position: fixed;
            inset: 0;
            background: var(--admin-backdrop);
            backdrop-filter: blur(4px);
            z-index: 200;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }

        .admin-modal-backdrop.active, .admin-drawer-backdrop.active {
            opacity: 1;
            visibility: visible;
        }

        .admin-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.95);
            background: var(--admin-bg-surface);
            border: 1px solid var(--admin-border-subtle);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 540px;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 210;
            box-shadow: var(--admin-shadow-lg);
            opacity: 0;
            visibility: hidden;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease, visibility 0.2s ease;
        }

        .admin-modal.active {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
            visibility: visible;
        }

        .admin-drawer {
            position: fixed;
            top: 0;
            bottom: 0;
            inset-inline-end: 0;
            width: 100%;
            max-width: 520px;
            background: var(--admin-bg-surface);
            border-inline-start: 1px solid var(--admin-border-subtle);
            z-index: 210;
            box-shadow: var(--admin-shadow-lg);
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
        }

        html[dir="rtl"] .admin-drawer {
            transform: translateX(-100%);
        }

        .admin-drawer.active {
            transform: translateX(0);
        }

        .drawer-header, .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--admin-border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .drawer-title, .modal-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .drawer-body, .modal-body {
            padding: 1.5rem;
            flex: 1;
            overflow-y: auto;
        }

        .drawer-footer, .modal-footer {
            padding: 1.15rem 1.5rem;
            border-top: 1px solid var(--admin-border-subtle);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            background: var(--admin-bg-surface-elevated);
        }

        .close-dialog-btn {
            background: transparent;
            border: none;
            color: var(--admin-text-muted);
            cursor: pointer;
            padding: 0.35rem;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-dialog-btn:hover {
            color: var(--admin-text-primary);
            background: var(--admin-bg-surface-elevated);
        }

        /* ==========================================================================
           RESPONSIVE BREAKPOINTS
           ========================================================================== */
        @media (max-width: 992px) {
            .admin-sidebar {
                position: fixed;
                left: 0;
                transform: translateX(-100%);
            }

            html[dir="rtl"] .admin-sidebar {
                left: auto;
                right: 0;
                transform: translateX(100%);
            }

            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }

            .sidebar-backdrop.mobile-open {
                display: block;
            }

            .sidebar-toggle-btn {
                display: flex;
            }

            .sidebar-close-btn {
                display: flex;
            }

            .admin-topbar {
                padding: 0.75rem 1.25rem;
            }

            .admin-content {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>
    @auth('admin')
        @include('rehla-admin::partials.sidebar')
    @endauth

    <div class="admin-main">
        @auth('admin')
            @include('rehla-admin::partials.topbar')
        @endauth

        <main class="admin-content">
            @include('rehla-admin::partials.toasts')
            @yield('content')
        </main>
    </div>

    {{-- Universal Modals & Drawers Backdrop --}}
    <div class="admin-modal-backdrop" id="admin-modal-backdrop"></div>
    <div class="admin-drawer-backdrop" id="admin-drawer-backdrop"></div>

    {{-- Universal Vanilla JS Logic --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Theme Toggle Logic
            const themeToggleBtn = document.getElementById('rehla-admin-theme-toggle');
            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('rehla_admin_theme', newTheme);
                });
            }

            // 2. Mobile Sidebar Toggle
            const sidebar = document.getElementById('admin-sidebar');
            const sidebarBackdrop = document.getElementById('sidebar-backdrop');
            const openSidebarBtn = document.getElementById('sidebar-toggle-btn');
            const closeSidebarBtn = document.getElementById('sidebar-close-btn');

            if (openSidebarBtn && sidebar && sidebarBackdrop) {
                openSidebarBtn.addEventListener('click', () => {
                    sidebar.classList.add('mobile-open');
                    sidebarBackdrop.classList.add('mobile-open');
                });

                const closeSidebar = () => {
                    sidebar.classList.remove('mobile-open');
                    sidebarBackdrop.classList.remove('mobile-open');
                };

                if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
                sidebarBackdrop.addEventListener('click', closeSidebar);
            }

            // 3. Sliding Drawers & Modals Handlers
            const modalBackdrop = document.getElementById('admin-modal-backdrop');
            const drawerBackdrop = document.getElementById('admin-drawer-backdrop');

            window.openDrawer = (drawerId) => {
                const drawer = document.getElementById(drawerId);
                if (drawer && drawerBackdrop) {
                    drawer.classList.add('active');
                    drawerBackdrop.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            };

            window.closeDrawer = (drawerId) => {
                const drawer = document.getElementById(drawerId);
                if (drawer && drawerBackdrop) {
                    drawer.classList.remove('active');
                    drawerBackdrop.classList.remove('active');
                    document.body.style.overflow = '';
                }
            };

            window.openModal = (modalId) => {
                const modal = document.getElementById(modalId);
                if (modal && modalBackdrop) {
                    modal.classList.add('active');
                    modalBackdrop.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            };

            window.closeModal = (modalId) => {
                const modal = document.getElementById(modalId);
                if (modal && modalBackdrop) {
                    modal.classList.remove('active');
                    modalBackdrop.classList.remove('active');
                    document.body.style.overflow = '';
                }
            };

            // Global Esc key listener for dialogs
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.admin-modal.active').forEach(m => m.classList.remove('active'));
                    document.querySelectorAll('.admin-drawer.active').forEach(d => d.classList.remove('active'));
                    if (modalBackdrop) modalBackdrop.classList.remove('active');
                    if (drawerBackdrop) drawerBackdrop.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });

            if (modalBackdrop) {
                modalBackdrop.addEventListener('click', () => {
                    document.querySelectorAll('.admin-modal.active').forEach(m => m.classList.remove('active'));
                    modalBackdrop.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            if (drawerBackdrop) {
                drawerBackdrop.addEventListener('click', () => {
                    document.querySelectorAll('.admin-drawer.active').forEach(d => d.classList.remove('active'));
                    drawerBackdrop.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            // 4. Live Table Search Filter Utility
            document.querySelectorAll('[data-table-search]').forEach(searchInput => {
                searchInput.addEventListener('input', (e) => {
                    const tableId = searchInput.getAttribute('data-table-search');
                    const query = e.target.value.toLowerCase().trim();
                    const table = document.getElementById(tableId);
                    if (!table) return;

                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(query) ? '' : 'none';
                    });
                });
            });
        });
    </script>
    @yield('scripts')
</body>
</html>
