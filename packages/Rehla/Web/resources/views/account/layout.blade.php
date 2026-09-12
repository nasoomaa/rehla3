@extends('rehla-web::layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Account Sidebar -->
        <aside class="lg:col-span-1">
            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-6">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                        {{ __('rehla-web::messages.account') }}
                    </div>
                    <div class="text-lg font-bold text-white truncate">
                        {{ auth('web')->user()->name }}
                    </div>
                    <div class="text-xs text-slate-400 truncate">
                        {{ auth('web')->user()->email }}
                    </div>
                </div>

                <nav class="space-y-1 text-sm font-medium">
                    <a href="/account/profile" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->is('account/profile*') ? 'bg-emerald-500/10 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        {{ __('rehla-web::messages.profile') }}
                    </a>
                    <a href="/account/travelers" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->is('account/travelers*') ? 'bg-emerald-500/10 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        {{ __('rehla-web::messages.travelers') }}
                    </a>
                    <a href="/account/wallet" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->is('account/wallet*') ? 'bg-emerald-500/10 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        {{ __('rehla-web::messages.wallet') }}
                    </a>
                    <a href="/account/top-ups" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->is('account/top-ups*') ? 'bg-emerald-500/10 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        {{ __('rehla-web::messages.top_ups') }}
                    </a>
                    <a href="/account/orders" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->is('account/orders*') ? 'bg-emerald-500/10 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        {{ __('rehla-web::messages.orders') }}
                    </a>
                    <a href="/account/notifications" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->is('account/notifications*') ? 'bg-emerald-500/10 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        {{ __('rehla-web::messages.notifications') }}
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Account Main View -->
        <div class="lg:col-span-3">
            @yield('account_content')
        </div>
    </div>
</div>
@endsection
