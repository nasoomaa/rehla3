@extends('rehla-web::layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-10 text-center max-w-2xl mx-auto">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
            {{ __('rehla-web::messages.browse_catalog') }}
        </h1>
        <p class="mt-3 text-slate-400 text-sm sm:text-base">
            {{ app()->getLocale() === 'ar' ? 'اختر الخدمة المطلوبة، اطلع على متطلبات التقديم والرسوم الرسمية بكل شفافية.' : 'Select a service to view transparent requirements, timelines, and verified pricing.' }}
        </p>
    </div>

    @if (empty($services))
        <div class="p-12 rounded-2xl bg-slate-900/30 border border-slate-800 text-center text-slate-400">
            {{ __('rehla-web::messages.no_records') }}
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($services as $service)
                <div class="rounded-2xl bg-slate-900/50 border border-slate-800 p-6 flex flex-col justify-between hover:border-slate-700 transition-colors">
                    <div>
                        <div class="text-xs font-semibold text-emerald-400 mb-2">
                            {{ app()->getLocale() === 'ar' ? $service->expectedDurationAr : $service->expectedDurationEn }}
                        </div>
                        <h2 class="text-xl font-bold text-white mb-2">
                            <a href="/services/{{ $service->slug }}" class="hover:text-emerald-400 transition-colors">
                                {{ app()->getLocale() === 'ar' ? $service->nameAr : $service->nameEn }}
                            </a>
                        </h2>
                        <p class="text-sm text-slate-400">
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
                        <a href="/services/{{ $service->slug }}" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition-colors">
                            {{ __('rehla-web::messages.order_now') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
