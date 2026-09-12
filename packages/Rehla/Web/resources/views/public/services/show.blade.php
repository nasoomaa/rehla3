@extends('rehla-web::layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb & Back -->
    <div class="mb-6">
        <a href="/services" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 flex items-center gap-2">
            &larr; {{ __('rehla-web::messages.services') }}
        </a>
    </div>

    <div class="rounded-3xl bg-slate-900/60 border border-slate-800 p-8 sm:p-12 shadow-2xl space-y-8">
        <!-- Header -->
        <div class="border-b border-slate-800 pb-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                    {{ app()->getLocale() === 'ar' ? $service->expectedDurationAr : $service->expectedDurationEn }}
                </span>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block">{{ __('rehla-web::messages.price') }}</span>
                    <span class="text-2xl sm:text-3xl font-black text-white">
                        {{ number_format($service->currentPriceMinor / 100, 2) }} <span class="text-sm font-medium text-slate-400">{{ __('rehla-web::messages.currency') }}</span>
                    </span>
                </div>
            </div>

            <h1 class="text-2xl sm:text-4xl font-extrabold text-white mt-4 tracking-tight">
                {{ app()->getLocale() === 'ar' ? $service->nameAr : $service->nameEn }}
            </h1>
            @if (app()->getLocale() === 'ar' && $service->nameEn)
                <p class="text-sm text-slate-400 mt-1">{{ $service->nameEn }}</p>
            @elseif (app()->getLocale() === 'en' && $service->nameAr)
                <p class="text-sm text-slate-400 mt-1">{{ $service->nameAr }}</p>
            @endif

            <p class="text-base text-slate-300 mt-4 leading-relaxed">
                {{ app()->getLocale() === 'ar' ? $service->detailedDescriptionAr : $service->detailedDescriptionEn }}
            </p>
        </div>

        <!-- Requirements Section -->
        @if (!empty($service->requirements))
            <div>
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    {{ __('rehla-web::messages.requirements') }}
                </h2>
                <ul class="space-y-3">
                    @foreach ($service->requirements as $req)
                        <li class="flex items-start gap-3 text-sm text-slate-300 bg-slate-950/40 p-3.5 rounded-xl border border-slate-800/80">
                            <span class="text-emerald-400 font-bold">&#10003;</span>
                            <span>{{ app()->getLocale() === 'ar' ? ($req['text_ar'] ?? $req['text_en']) : ($req['text_en'] ?? $req['text_ar']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Notes Section -->
        @if ($service->notesEn || $service->notesAr)
            <div class="p-5 rounded-2xl bg-amber-950/20 border border-amber-500/20 text-amber-200/90 text-sm">
                <span class="font-bold block mb-1">{{ __('rehla-web::messages.notes') }}:</span>
                <p>{{ app()->getLocale() === 'ar' ? ($service->notesAr ?? $service->notesEn) : ($service->notesEn ?? $service->notesAr) }}</p>
            </div>
        @endif

        <!-- Action CTAs: Order Now & WhatsApp -->
        <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-end gap-4">
            @if (!empty($inquiryLink))
                <a href="{{ $inquiryLink->url }}" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto px-6 py-3.5 rounded-xl border border-emerald-500/40 hover:bg-emerald-500/10 text-emerald-400 font-bold text-sm text-center transition-colors flex items-center justify-center gap-2">
                    <span>💬</span>
                    <span>{{ __('rehla-web::messages.whatsapp_inquiry') }}</span>
                </a>
            @endif

            <a href="/checkout/{{ $service->slug }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm text-center shadow-lg shadow-emerald-500/25 transition-all">
                {{ __('rehla-web::messages.order_now') }}
            </a>
        </div>
    </div>
</div>
@endsection
