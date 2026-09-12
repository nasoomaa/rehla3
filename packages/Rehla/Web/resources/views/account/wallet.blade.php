@extends('rehla-web::account.layout')

@section('account_content')
<div class="rounded-3xl bg-slate-900/50 border border-slate-800 p-8 space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('rehla-web::messages.wallet') }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ app()->getLocale() === 'ar' ? 'رصيدك المتاح لدفع رسوم الخدمات فورياً' : 'Your balance available for instant order checkout' }}</p>
        </div>
        <a href="/account/top-ups/create" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20 transition-all">
            + {{ __('rehla-web::messages.top_ups') }}
        </a>
    </div>

    <!-- Balance Card -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-950/40 to-slate-950 border border-emerald-500/30 flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400 block mb-1">
                {{ __('rehla-web::messages.balance') }}
            </span>
            <div class="text-3xl font-black text-white">
                {{ number_format(($balanceMinor ?? 0) / 100, 2) }} <span class="text-sm font-normal text-slate-400">{{ __('rehla-web::messages.currency') }}</span>
            </div>
        </div>
    </div>

    <!-- Ledger Entries -->
    <div>
        <h2 class="text-base font-bold text-white mb-4">{{ app()->getLocale() === 'ar' ? 'سجل العمليات' : 'Transaction History' }}</h2>
        @if (empty($entries))
            <div class="p-10 rounded-2xl bg-slate-950 border border-slate-800 text-center text-slate-400 text-sm">
                {{ __('rehla-web::messages.no_records') }}
            </div>
        @else
            <div class="divide-y divide-slate-800 border-t border-slate-800">
                @foreach ($entries as $entry)
                    <div class="py-4 flex items-center justify-between gap-4">
                        <div>
                            <span class="text-sm font-semibold text-white block">{{ $entry->description ?? ($entry->direction === 'credit' ? 'Top-Up Credit' : 'Order Debit') }}</span>
                            <span class="text-xs text-slate-500">{{ $entry->createdAt }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold {{ $entry->direction === 'credit' ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $entry->direction === 'credit' ? '+' : '-' }} {{ number_format($entry->amountMinor / 100, 2) }} {{ __('rehla-web::messages.currency') }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
