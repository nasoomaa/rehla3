<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="/account/orders" class="inline-flex items-center text-xs font-semibold text-slate-400 hover:text-white transition-colors mb-2">
                ← {{ __('Back to Orders') }}
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-white font-mono">
                    #{{ substr($order->id, 0, 8) }}
                </h1>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                    {{ ($execution?->status ?? 'order_received') === 'completed' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' :
                       (($execution?->status ?? 'order_received') === 'customer_action_required' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse' :
                       (($execution?->status ?? 'order_received') === 'cancelled' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' :
                       'bg-sky-500/20 text-sky-400 border border-sky-500/30')) }}">
                    {{ str_replace('_', ' ', $execution?->status ?? 'order_received') }}
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                {{ __('Placed on :date', ['date' => $order->createdAt->format('Y-m-d H:i')]) }}
            </p>
        </div>

        <div class="text-right">
            <span class="text-xs text-slate-400 block">{{ __('Amount Paid') }}</span>
            <span class="text-2xl font-black text-white font-mono">
                {{ number_format($order->priceMinor / 100, 2) }} <span class="text-sm font-normal text-slate-400">{{ $order->currency }}</span>
            </span>
        </div>
    </div>

    <!-- Action Required Banner (if any open action requests) -->
    @if ($execution && ! empty($execution->actionRequests))
        @foreach ($execution->actionRequests as $action)
            @if ($action->status === 'open')
                <div class="p-5 rounded-2xl bg-amber-950/40 border border-amber-500/40 flex flex-wrap items-center justify-between gap-4">
                    <div class="space-y-1">
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-bold text-[10px] uppercase tracking-wider">
                            {{ __('Action Required') }}
                        </span>
                        <p class="text-sm font-bold text-white">
                            {{ app()->getLocale() === 'ar' ? $action->descriptionAr : $action->descriptionEn }}
                        </p>
                        @if ($action->dueAt)
                            <p class="text-xs text-amber-300/80">
                                {{ __('Due by: :date', ['date' => $action->dueAt->format('Y-m-d H:i')]) }}
                            </p>
                        @endif
                    </div>
                    <a href="/account/actions/{{ $action->id }}" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-md shadow-amber-500/20 transition-all">
                        {{ __('Respond Now') }} →
                    </a>
                </div>
            @endif
        @endforeach
    @endif

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Service Details -->
        <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">
                {{ __('Service Snapshot') }}
            </h2>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-500">{{ __('Service Name:') }}</span>
                    <span class="text-white font-bold ml-1">
                        {{ app()->getLocale() === 'ar' ? $order->serviceSnapshot->nameAr : $order->serviceSnapshot->nameEn }}
                    </span>
                </div>
                @if (! empty($order->serviceSnapshot->expectedDuration))
                    <div>
                        <span class="text-slate-500">{{ __('Expected Duration:') }}</span>
                        <span class="text-slate-300 ml-1">
                            {{ $order->serviceSnapshot->expectedDuration[app()->getLocale()] ?? ($order->serviceSnapshot->expectedDuration['en'] ?? '') }}
                        </span>
                    </div>
                @endif
                @if (! empty($order->serviceSnapshot->descriptions['short_en']) || ! empty($order->serviceSnapshot->descriptions['short_ar']))
                    <p class="text-slate-400 mt-2">
                        {{ app()->getLocale() === 'ar' ? ($order->serviceSnapshot->descriptions['short_ar'] ?? $order->serviceSnapshot->descriptions['short_en'] ?? '') : ($order->serviceSnapshot->descriptions['short_en'] ?? '') }}
                    </p>
                @endif
            </div>
        </div>

        <!-- Traveler Details -->
        <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">
                {{ __('Traveler Snapshot') }}
            </h2>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-500">{{ __('Full Name:') }}</span>
                    <span class="text-white font-bold ml-1">{{ $order->travelerSnapshot->fullName }}</span>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('Passport Number:') }}</span>
                    <span class="text-white font-mono ml-1">{{ $order->travelerSnapshot->passportNumber }}</span>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('Date of Birth:') }}</span>
                    <span class="text-slate-300 ml-1">{{ $order->travelerSnapshot->dateOfBirth }}</span>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('Passport Expires:') }}</span>
                    <span class="text-slate-300 ml-1">{{ $order->travelerSnapshot->passportExpiresAt ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Form Answers Snapshot -->
    @if (! empty($order->formSnapshot->answers))
        <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">
                {{ __('Submitted Application Data') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                @foreach ($order->formSnapshot->answers as $q => $ans)
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800/80">
                        <span class="text-slate-500 block mb-1 font-mono">{{ str_replace('_', ' ', Str::title((string) $q)) }}</span>
                        <span class="text-white font-semibold">{{ is_array($ans) ? json_encode($ans) : $ans }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Status History / Timeline -->
    @if ($execution && ! empty($execution->history))
        <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">
                {{ __('Fulfillment Progress Timeline') }}
            </h2>
            <div class="space-y-4">
                @foreach ($execution->history as $entry)
                    <div class="flex items-start gap-4">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0"></div>
                        <div class="space-y-0.5 text-xs">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white uppercase">{{ str_replace('_', ' ', $entry->toStatus) }}</span>
                                <span class="text-slate-500 font-mono">{{ $entry->createdAt->format('Y-m-d H:i') }}</span>
                            </div>
                            @if ($entry->reason)
                                <p class="text-slate-400">{{ $entry->reason }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
