<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('rehla-web::messages.brand') }} - {{ __('rehla-web::messages.tagline') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: {{ app()->getLocale() === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" }};
        }
    </style>
</head>
<body class="min-h-full flex flex-col antialiased selection:bg-emerald-500 selection:text-white">
    <!-- Navbar -->
    <header class="sticky top-0 z-50 backdrop-blur-md bg-slate-950/80 border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="/" class="flex items-center gap-2 group">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center font-black text-slate-950 text-lg shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                        ر
                    </span>
                    <span class="text-xl font-bold tracking-tight text-white group-hover:text-emerald-400 transition-colors">
                        {{ __('rehla-web::messages.brand') }}
                    </span>
                </a>
                <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                    <a href="/" class="text-slate-300 hover:text-white transition-colors">{{ __('rehla-web::messages.home') }}</a>
                    <a href="/services" class="text-slate-300 hover:text-white transition-colors">{{ __('rehla-web::messages.services') }}</a>
                </nav>
            </div>

            <div class="flex items-center gap-4">
                <!-- Locale Switcher -->
                <div class="flex items-center text-xs font-semibold bg-slate-900 border border-slate-800 rounded-lg p-1">
                    <a href="/locale/ar" class="px-2.5 py-1 rounded {{ app()->getLocale() === 'ar' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-white' }}">العربية</a>
                    <a href="/locale/en" class="px-2.5 py-1 rounded {{ app()->getLocale() === 'en' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-white' }}">English</a>
                </div>

                <!-- Auth Buttons -->
                @auth('web')
                    <a href="/account/profile" class="text-sm font-medium text-slate-300 hover:text-emerald-400 transition-colors flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-xs text-emerald-400 font-bold">
                            {{ mb_substr(auth('web')->user()->name, 0, 1) }}
                        </span>
                        <span class="hidden sm:inline">{{ auth('web')->user()->name }}</span>
                    </a>
                    <form action="/logout" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-700 hover:bg-slate-800 text-slate-300 transition-colors">
                            {{ __('rehla-web::messages.logout') }}
                        </button>
                    </form>
                @else
                    <a href="/login" class="text-sm font-medium text-slate-300 hover:text-white transition-colors px-3 py-1.5">
                        {{ __('rehla-web::messages.login') }}
                    </a>
                    <a href="/register" class="text-sm font-semibold px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-md shadow-emerald-500/20 transition-all">
                        {{ __('rehla-web::messages.register') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Flash Alerts -->
    @if (session('status'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/30 text-emerald-300 text-sm">
                {{ session('status') }}
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="p-4 rounded-xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 bg-slate-950/60 py-12 mt-20 text-slate-500 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                &copy; {{ date('Y') }} {{ __('rehla-web::messages.brand') }}. All rights reserved.
            </div>
            <div class="flex gap-6 text-xs">
                <span>Phase 1 Verified &bull; Sudanese Pound (SDG)</span>
            </div>
        </div>
    </footer>
</body>
</html>
