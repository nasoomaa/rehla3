@extends('rehla-web::account.layout')

@section('account_content')
<div class="rounded-3xl bg-slate-900/50 border border-slate-800 p-8 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-white">{{ __('rehla-web::messages.profile') }}</h1>
        <p class="text-xs text-slate-400 mt-1">{{ app()->getLocale() === 'ar' ? 'معلومات حسابك الأساسية' : 'Your personal account credentials' }}</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-slate-800">
        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800/80">
            <span class="text-xs text-slate-500 block mb-1">{{ __('rehla-web::messages.full_name') }}</span>
            <span class="text-base font-semibold text-white">{{ auth('web')->user()->name }}</span>
        </div>

        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800/80">
            <span class="text-xs text-slate-500 block mb-1">{{ __('rehla-web::messages.email') }}</span>
            <span class="text-base font-semibold text-white">{{ auth('web')->user()->email }}</span>
        </div>

        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800/80">
            <span class="text-xs text-slate-500 block mb-1">{{ __('rehla-web::messages.status') }}</span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                {{ auth('web')->user()->status ?? 'active' }}
            </span>
        </div>
    </div>
</div>
@endsection
