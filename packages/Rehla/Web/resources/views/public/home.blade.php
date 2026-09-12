@extends('rehla-web::layouts.app')

@section('content')
<!-- Hero Section -->
<section class="relative overflow-hidden py-20 lg:py-28 border-b border-slate-800/60 bg-gradient-to-b from-slate-900/40 via-slate-950 to-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 mb-6">
            {{ __('rehla-web::messages.brand') }} &bull; {{ __('rehla-web::messages.currency') }}
        </span>
        <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-tight">
            {{ __('rehla-web::messages.tagline') }}
        </h1>
        <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto">
            {{ app()->getLocale() === 'ar' ? 'معالجة فورية وتوثيق رقمي آمن للتأشيرات، المعاملات، والمستندات بأسعار واضحة ودون وسطاء.' : 'Streamlined, secure digital processing for visas, attestations, and travel documentation with clear pricing and zero middlemen.' }}
        </p>
        <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
            <a href="/services" class="px-6 py-3.5 rounded-xl font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-500/25 transition-all text-base">
                {{ __('rehla-web::messages.browse_catalog') }}
            </a>
            @guest('web')
                <a href="/register" class="px-6 py-3.5 rounded-xl font-bold bg-slate-900 hover:bg-slate-800 border border-slate-700 text-white transition-all text-base">
                    {{ __('rehla-web::messages.register') }}
                </a>
            @endguest
        </div>
    </div>
</section>

<!-- Featured Services -->
<section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-white">{{ __('rehla-web::messages.services') }}</h2>
            <p class="text-sm text-slate-400 mt-1">{{ __('rehla-web::messages.browse_catalog') }}</p>
        </div>
        <a href="/services" class="text-sm font-semibold text-emerald-400 hover:text-emerald-300">
            {{ __('rehla-web::messages.browse_catalog') }} &rarr;
        </a>
    </div>

    @if (empty($services))
        <div class="p-12 rounded-2xl bg-slate-900/30 border border-slate-800 text-center text-slate-400">
            {{ __('rehla-web::messages.no_records') }}
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($services as $service)
                <div class="rounded-2xl bg-slate-900/50 border border-slate-800/80 p-6 flex flex-col justify-between hover:border-slate-700 transition-colors group">
                    <div>
                        <div class="text-xs font-semibold text-emerald-400 mb-2">
                            {{ app()->getLocale() === 'ar' ? $service->expectedDurationAr : $service->expectedDurationEn }}
                        </div>
                        <h3 class="text-xl font-bold text-white group-hover:text-emerald-400 transition-colors">
                            {{ app()->getLocale() === 'ar' ? $service->nameAr : $service->nameEn }}
                        </h3>
                        <p class="mt-2 text-sm text-slate-400 line-clamp-2">
                            {{ app()->getLocale() === 'ar' ? $service->shortDescriptionAr : $service->shortDescriptionEn }}
                        </p>
                    </div>

                    <div class="mt-6 pt-6 border-t border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-500 block">{{ __('rehla-web::messages.price') }}</span>
                            <span class="text-lg font-extrabold text-white">
                                {{ number_format($service->currentPriceMinor / 100, 2) }} <span class="text-xs font-normal text-slate-400">{{ __('rehla-web::messages.currency') }}</span>
                            </span>
                        </div>
                        <a href="/services/{{ $service->slug }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold transition-colors">
                            {{ __('rehla-web::messages.order_now') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection
