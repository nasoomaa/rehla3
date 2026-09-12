@extends('rehla-web::layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800 shadow-xl space-y-6">
        <div class="text-center">
            <h1 class="text-2xl font-extrabold text-white">{{ __('rehla-web::messages.login') }}</h1>
            <p class="text-xs text-slate-400 mt-2">
                {{ app()->getLocale() === 'ar' ? 'سجل دخولك لمتابعة معاملاتك ورصيد محفظتك' : 'Log in to track your orders and manage your wallet' }}
            </p>
        </div>

        <form action="/login" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">{{ __('rehla-web::messages.email') }}</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 text-sm">
                @error('email')
                    <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 mb-1.5">{{ __('rehla-web::messages.password') }}</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 text-sm">
                @error('password')
                    <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm shadow-md shadow-emerald-500/20 transition-all">
                {{ __('rehla-web::messages.login') }}
            </button>
        </form>

        <div class="text-center text-xs text-slate-400 pt-4 border-t border-slate-800/80">
            <span>{{ app()->getLocale() === 'ar' ? 'ليس لديك حساب؟' : "Don't have an account?" }}</span>
            <a href="/register" class="text-emerald-400 font-semibold hover:underline">{{ __('rehla-web::messages.register') }}</a>
        </div>
    </div>
</div>
@endsection
