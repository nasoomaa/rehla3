@extends('rehla-web::account.layout')

@section('account_content')
<div class="rounded-3xl bg-slate-900/50 border border-slate-800 p-8 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('rehla-web::messages.travelers') }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ app()->getLocale() === 'ar' ? 'سجل المسافرين المحفوظين لتقديم الطلبات بسرعة' : 'Saved traveler profiles for faster order checkout' }}</p>
        </div>
        <a href="/account/travelers/create" class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20 transition-all">
            + {{ __('rehla-web::messages.add_new') }}
        </a>
    </div>

    @if (empty($travelers))
        <div class="p-12 rounded-2xl bg-slate-950 border border-slate-800 text-center text-slate-400 text-sm">
            {{ __('rehla-web::messages.no_records') }}
        </div>
    @else
        <div class="divide-y divide-slate-800 border-t border-slate-800">
            @foreach ($travelers as $t)
                <div class="py-4 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-white">{{ $t->fullName }}</h2>
                        <div class="text-xs text-slate-400 flex items-center gap-4 mt-1">
                            <span>{{ __('rehla-web::messages.travelers') }}: {{ $t->passportNumber }}</span>
                            <span>DOB: {{ $t->dateOfBirth }}</span>
                            <span>Expires: {{ $t->passportExpiresAt }}</span>
                        </div>
                    </div>
                    <a href="/account/travelers/{{ $t->id }}/edit" class="px-3 py-1.5 rounded-lg border border-slate-700 hover:bg-slate-800 text-xs font-semibold text-slate-300 transition-colors">
                        {{ __('rehla-web::messages.edit') }}
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
