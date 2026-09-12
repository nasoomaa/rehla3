<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6">
    <!-- Breadcrumb & Title -->
    <div class="mb-6">
        <a href="/catalog" class="inline-flex items-center text-xs font-semibold text-slate-400 hover:text-white transition-colors mb-2">
            ← {{ __('Back to Services') }}
        </a>
        <h1 class="text-2xl font-bold text-white">
            {{ __('Checkout: :service', ['service' => app()->getLocale() === 'ar' ? $serviceNameAr : $serviceNameEn]) }}
        </h1>
    </div>

    <!-- Stepper Navigation -->
    <div class="mb-8 grid grid-cols-3 gap-3">
        <div class="p-3 rounded-xl border {{ $step === 'traveler' ? 'bg-emerald-950/40 border-emerald-500 text-emerald-400' : 'bg-slate-900/40 border-slate-800 text-slate-400' }} text-center">
            <span class="text-xs font-bold block uppercase tracking-wider">1. {{ __('Select Traveler') }}</span>
        </div>
        <div class="p-3 rounded-xl border {{ $step === 'form' ? 'bg-emerald-950/40 border-emerald-500 text-emerald-400' : 'bg-slate-900/40 border-slate-800 text-slate-400' }} text-center">
            <span class="text-xs font-bold block uppercase tracking-wider">2. {{ __('Service Details') }}</span>
        </div>
        <div class="p-3 rounded-xl border {{ $step === 'review' ? 'bg-emerald-950/40 border-emerald-500 text-emerald-400' : 'bg-slate-900/40 border-slate-800 text-slate-400' }} text-center">
            <span class="text-xs font-bold block uppercase tracking-wider">3. {{ __('Review & Pay') }}</span>
        </div>
    </div>

    @if ($step === 'traveler')
        <!-- STEP 1: Traveler Selection -->
        <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-white">{{ __('Choose the traveler for this service') }}</h2>
                    <p class="text-xs text-slate-400 mt-0.5">{{ __('Select from your saved travelers profile or add a new traveler.') }}</p>
                </div>
                <a href="/account/travelers/create" target="_blank" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300">
                    + {{ __('New Traveler') }}
                </a>
            </div>

            @if (empty($travelers))
                <div class="p-8 rounded-xl bg-slate-950 border border-slate-800 text-center">
                    <p class="text-sm text-slate-400 mb-3">{{ __('You do not have any saved travelers yet.') }}</p>
                    <a href="/account/travelers/create" class="inline-block px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs">
                        {{ __('Add Traveler Profile') }}
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($travelers as $t)
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all {{ $travelerId === $t->id ? 'bg-emerald-950/30 border-emerald-500 shadow-sm shadow-emerald-500/10' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-bold text-white">{{ $t->fullName }}</span>
                                <input type="radio" wire:model.live="travelerId" value="{{ $t->id }}" class="text-emerald-500 focus:ring-emerald-400">
                            </div>
                            <div class="text-xs text-slate-400 space-y-1">
                                <div><span class="text-slate-500">{{ __('Passport:') }}</span> <span class="font-mono text-slate-200">{{ $t->passportNumber }}</span></div>
                                <div><span class="text-slate-500">{{ __('Expires:') }}</span> <span class="text-slate-300">{{ $t->passportExpiresAt }}</span></div>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif

            @error('travelerId')
                <p class="text-xs text-rose-400">{{ $message }}</p>
            @enderror

            <div class="flex justify-end pt-4 border-t border-slate-800">
                <button wire:click="nextStep" type="button" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20 transition-all">
                    {{ __('Continue to Service Form') }} →
                </button>
            </div>
        </div>
    @elseif ($step === 'form')
        <!-- STEP 2: Service Form Requirements -->
        <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-6">
            <div>
                <h2 class="text-base font-bold text-white">{{ __('Complete Required Service Details') }}</h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ __('Please fill out all mandatory fields requested by the service provider.') }}</p>
            </div>

            @if (empty($formFields))
                <div class="p-4 rounded-xl bg-slate-950 text-xs text-slate-400">
                    {{ __('No additional questions required for this service.') }}
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($formFields as $field)
                        @php
                            $fieldKey = $field['key'];
                            $label = app()->getLocale() === 'ar' ? ($field['labelAr'] ?? $fieldKey) : ($field['labelEn'] ?? $fieldKey);
                            $isRequired = (bool) ($field['required'] ?? false);
                            $type = $field['type'] ?? 'short_text';
                        @endphp
                        <div>
                            <label for="field-{{ $fieldKey }}" class="block text-sm font-semibold text-white mb-1.5">
                                {{ $label }} @if ($isRequired) <span class="text-rose-400">*</span> @endif
                            </label>

                            @if ($type === 'long_text')
                                <textarea id="field-{{ $fieldKey }}" wire:model.defer="formData.{{ $fieldKey }}" rows="3"
                                    class="block w-full rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 px-4 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"></textarea>
                            @elseif ($type === 'select')
                                <select id="field-{{ $fieldKey }}" wire:model.defer="formData.{{ $fieldKey }}"
                                    class="block w-full rounded-xl bg-slate-950 border border-slate-800 text-white px-4 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm">
                                    <option value="">{{ __('Select an option') }}</option>
                                    @foreach ($field['options'] ?? [] as $opt)
                                        <option value="{{ is_array($opt) ? ($opt['value'] ?? '') : $opt }}">{{ is_array($opt) ? ($opt['label'] ?? $opt['value']) : $opt }}</option>
                                    @endforeach
                                </select>
                            @elseif ($type === 'number')
                                <input type="number" id="field-{{ $fieldKey }}" wire:model.defer="formData.{{ $fieldKey }}"
                                    class="block w-full rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 px-4 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm">
                            @elseif ($type === 'date')
                                <input type="date" id="field-{{ $fieldKey }}" wire:model.defer="formData.{{ $fieldKey }}"
                                    class="block w-full rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 px-4 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm">
                            @elseif ($type === 'checkbox')
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="field-{{ $fieldKey }}" wire:model.defer="formData.{{ $fieldKey }}" value="1"
                                        class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-emerald-400">
                                    <span class="text-xs text-slate-300">{{ $label }}</span>
                                </label>
                            @else
                                <input type="text" id="field-{{ $fieldKey }}" wire:model.defer="formData.{{ $fieldKey }}"
                                    class="block w-full rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 px-4 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm">
                            @endif

                            @error("formData.{$fieldKey}")
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                <button wire:click="previousStep" type="button" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white text-xs font-semibold transition-colors">
                    ← {{ __('Back to Traveler') }}
                </button>
                <button wire:click="nextStep" type="button" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20 transition-all">
                    {{ __('Review Order') }} →
                </button>
            </div>
        </div>
    @elseif ($step === 'review')
        <!-- STEP 3: Review & Price Freeze -->
        <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-6">
            <div>
                <h2 class="text-base font-bold text-white">{{ __('Review Order & Confirm Payment') }}</h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ __('Price is frozen for this transaction. Funds will be deducted from your Rehla Wallet.') }}</p>
            </div>

            <!-- Price Card -->
            <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-950/40 to-slate-950 border border-emerald-500/30 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400 block mb-1">
                        {{ __('Total Frozen Price') }}
                    </span>
                    <div class="text-3xl font-black text-white">
                        {{ number_format(($acceptedPriceMinor ?? $priceMinor) / 100, 2) }} <span class="text-sm font-normal text-slate-400">SDG</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block">{{ __('Your Wallet Balance') }}</span>
                    <span class="text-sm font-bold {{ $walletBalanceMinor >= ($acceptedPriceMinor ?? $priceMinor) ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ number_format($walletBalanceMinor / 100, 2) }} SDG
                    </span>
                </div>
            </div>

            <!-- Traveler & Details Breakdown -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                    <span class="font-bold text-white block text-sm mb-1">{{ __('Traveler Information') }}</span>
                    <div><span class="text-slate-500">{{ __('Name:') }}</span> <span class="text-slate-200 font-semibold">{{ $selectedTraveler?->fullName }}</span></div>
                    <div><span class="text-slate-500">{{ __('Passport:') }}</span> <span class="font-mono text-slate-200">{{ $selectedTraveler?->passportNumber }}</span></div>
                    <div><span class="text-slate-500">{{ __('Expiry:') }}</span> <span class="text-slate-200">{{ $selectedTraveler?->passportExpiresAt }}</span></div>
                </div>

                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                    <span class="font-bold text-white block text-sm mb-1">{{ __('Service Summary') }}</span>
                    <div><span class="text-slate-500">{{ __('Service:') }}</span> <span class="text-slate-200 font-semibold">{{ app()->getLocale() === 'ar' ? $serviceNameAr : $serviceNameEn }}</span></div>
                    <div><span class="text-slate-500">{{ __('Price Version:') }}</span> <span class="font-mono text-slate-200">v{{ $acceptedPriceVersion ?? 1 }}</span></div>
                    <div><span class="text-slate-500">{{ __('Payment Method:') }}</span> <span class="text-emerald-400 font-semibold">{{ __('Rehla Wallet') }}</span></div>
                </div>
            </div>

            @error('wallet')
                <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-800 text-rose-300 text-xs flex items-center justify-between">
                    <span>{{ $message }}</span>
                    <a href="/account/top-ups/create" target="_blank" class="font-bold underline ml-4 hover:text-rose-100">
                        {{ __('Top-up Wallet') }} →
                    </a>
                </div>
            @enderror

            <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                <button wire:click="previousStep" type="button" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white text-xs font-semibold transition-colors">
                    ← {{ __('Back to Form') }}
                </button>
                <button wire:click="submitOrder" wire:loading.attr="disabled" type="button"
                    class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20 transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="submitOrder">{{ __('Confirm & Pay with Wallet') }}</span>
                    <span wire:loading wire:target="submitOrder">{{ __('Processing Order...') }}</span>
                </button>
            </div>
        </div>
    @endif
</div>
