<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Operations Panel') — Rehla Admin</title>
    <style>
        :root {
            --bg-body: #0b1329;
            --bg-card: #152243;
            --bg-card-hover: #1c2e5a;
            --border: #243b73;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: var(--bg-body); color: var(--text-main); display: flex; min-height: 100vh; }
        aside { width: 260px; background: #080d1c; border-inline-end: 1px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; }
        .brand { padding: 1.25rem 1.5rem; font-size: 1.2rem; font-weight: 700; color: #60a5fa; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        nav { padding: 1rem 0.5rem; flex: 1; overflow-y: auto; }
        nav a { display: block; padding: 0.6rem 1rem; color: var(--text-muted); text-decoration: none; border-radius: 0.375rem; margin-bottom: 0.25rem; font-size: 0.9rem; transition: all 0.15s; }
        nav a:hover, nav a.active { background: var(--bg-card); color: #fff; }
        main { flex: 1; display: flex; flex-direction: column; overflow-x: hidden; }
        header { background: #0d1833; border-bottom: 1px solid var(--border); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .content { padding: 2rem; flex: 1; }
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.25rem; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem 1rem; text-align: start; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
        tr:hover td { background: var(--bg-card-hover); }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .badge-info { background: rgba(37, 99, 235, 0.2); color: #60a5fa; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: background 0.15s; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.375rem; font-size: 0.85rem; color: var(--text-muted); }
        input, select, textarea { width: 100%; padding: 0.6rem 0.75rem; background: #0b1329; border: 1px solid var(--border); border-radius: 0.375rem; color: #fff; font-size: 0.9rem; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); }
        .alert { padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; font-size: 0.9rem; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #34d399; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; }
        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 0.5rem; padding: 1.25rem; }
        .stat-title { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: #fff; }
    </style>
</head>
<body>
    @auth('admin')
    <aside>
        <div class="brand">
            <span>Rehla Admin</span>
            <span style="font-size: 0.7rem; background: #1e3a8a; color: #93c5fd; padding: 2px 6px; border-radius: 4px;">OPS</span>
        </div>
        <nav>
            <a href="/admin/overview">📊 Overview</a>
            <a href="/admin/services">✈️ Services</a>
            <a href="/admin/application-forms">📝 Application Forms</a>
            <a href="/admin/customers">👥 Customers</a>
            <a href="/admin/travelers">🧳 Travelers</a>
            <a href="/admin/wallets">💳 Wallets</a>
            <a href="/admin/bank-accounts">🏦 Bank Accounts</a>
            <a href="/admin/top-up-requests">💰 Top-up Requests</a>
            <a href="/admin/orders">📦 Orders</a>
            <a href="/admin/service-executions">⚡ Service Executions</a>
            <a href="/admin/content">📄 Content</a>
            <a href="/admin/notifications">🔔 Notifications</a>
            <a href="/admin/roles-permissions">🛡️ Roles & Permissions</a>
            <a href="/admin/audit-log">📜 Audit Log</a>
        </nav>
        <div style="padding: 1rem; border-top: 1px solid var(--border); font-size: 0.8rem; color: var(--text-muted);">
            <div>{{ Auth::guard('admin')->user()->name ?? 'Staff User' }}</div>
            <form method="POST" action="/admin/logout" style="margin-top: 0.5rem;">
                @csrf
                <button type="submit" class="btn btn-danger" style="width: 100%; font-size: 0.75rem; padding: 0.35rem;">Log Out</button>
            </form>
        </div>
    </aside>
    @endauth

    <main>
        @auth('admin')
        <header>
            <h1>@yield('title', 'Admin Operations')</h1>
            <div>
                <a href="/admin/mfa" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.35rem 0.75rem;">Confirm MFA</a>
            </div>
        </header>
        @endauth

        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</body>
</html>
