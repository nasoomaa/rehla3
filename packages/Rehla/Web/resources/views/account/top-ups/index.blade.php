@extends('rehla-web::account.layout')

@section('account_content')
<div class="rounded-3xl bg-slate-900/50 border border-slate-800 p-8 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('rehla-web::messages.top_ups') }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ app()->getLocale() === 'ar' ? 'طلبات الشحن البنكي السابقة وحالات مراجعتها' : 'Bank transfer top-up requests and review statuses' }}</p>
        </div>
        <a href="/account/top-ups/create" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20 transition-all">
            + {{ __('rehla-web::messages.add_new') }}
        </a>
    </div>

    @if (empty($topUps))
        <div class="p-12 rounded-2xl bg-slate-950 border border-slate-800 text-center text-slate-400 text-sm">
            {{ __('rehla-web::messages.no_records') }}
        </div>
    @else
        <div class="divide-y divide-slate-800 border-t border-slate-800">
            @foreach ($topUps as $tu)
                <div class="py-4 flex items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-white">Ref: {{ $tu->referenceNumber }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $tu->status->value === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : '' }}
                                {{ $tu->status->value === 'rejected' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : '' }}
                                {{ $tu->status->value === 'under_review' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : '' }}
                            ">
                                {{ $tu->status->value }}
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 block mt-1">
                            {{ $tu->createdAt }}
                        </span>
                        @if ($tu->rejectionReason)
                            <p class="text-xs text-rose-400 mt-1">Reason: {{ $tu->rejectionReason }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-base font-extrabold text-white block">
                            {{ number_format($tu->amountMinor / 100, 2) }} {{ __('rehla-web::messages.currency') }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
