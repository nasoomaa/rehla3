@extends('rehla-web::account.layout')

@section('account_content')
<div class="rounded-3xl bg-slate-900/50 border border-slate-800 p-8 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-white">{{ __('rehla-web::messages.orders') }}</h1>
        <p class="text-xs text-slate-400 mt-1">{{ app()->getLocale() === 'ar' ? 'سجل طلباتك وحالات إنجازها' : 'Your order history and fulfillment progress' }}</p>
    </div>

    @if (empty($orders))
        <div class="p-12 rounded-2xl bg-slate-950 border border-slate-800 text-center text-slate-400 text-sm">
            {{ __('rehla-web::messages.no_records') }}
        </div>
    @else
        <div class="divide-y divide-slate-800 border-t border-slate-800">
            @foreach ($orders as $order)
                <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-white">#{{ $order->orderNumber }}</h2>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                {{ $order->status->value }}
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 block mt-1">
                            {{ $order->createdAt }}
                        </span>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <span class="text-base font-extrabold text-white block">
                                {{ number_format($order->totalAmountMinor / 100, 2) }} {{ __('rehla-web::messages.currency') }}
                            </span>
                        </div>
                        <a href="/account/orders/{{ $order->id }}" class="px-3.5 py-1.5 rounded-lg border border-slate-700 hover:bg-slate-800 text-xs font-semibold text-slate-300 transition-colors">
                            Details &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
