@extends('rehla-web::account.layout')

@section('account_content')
<div class="rounded-3xl bg-slate-900/50 border border-slate-800 p-8 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-white">{{ __('rehla-web::messages.notifications') }}</h1>
        <p class="text-xs text-slate-400 mt-1">{{ app()->getLocale() === 'ar' ? 'تنبيهات النظام وتحديثات المعاملات' : 'System updates and transaction alerts' }}</p>
    </div>

    @if (empty($notifications))
        <div class="p-12 rounded-2xl bg-slate-950 border border-slate-800 text-center text-slate-400 text-sm">
            {{ __('rehla-web::messages.no_records') }}
        </div>
    @else
        <div class="divide-y divide-slate-800 border-t border-slate-800">
            @foreach ($notifications as $n)
                <div class="py-4 flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="w-2 h-2 rounded-full mt-1.5 {{ $n->readAt ? 'bg-slate-700' : 'bg-emerald-400' }}"></span>
                        <div>
                            <span class="text-sm font-bold text-white block">{{ $n->type }}</span>
                            <pre class="text-xs text-slate-400 font-mono mt-1 whitespace-pre-wrap">{{ json_encode($n->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            <span class="text-[10px] text-slate-500 block mt-1">{{ $n->createdAt }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
