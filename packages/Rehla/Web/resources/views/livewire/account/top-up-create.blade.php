<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="/account/top-ups" class="inline-flex items-center text-xs font-semibold text-slate-400 hover:text-white transition-colors mb-2">
                ← {{ __('Back to Top-Ups') }}
            </a>
            <h1 class="text-2xl font-bold text-white">{{ __('Submit Top-Up Request') }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ __('Transfer funds to one of our verified company bank accounts and submit your receipt for review.') }}</p>
        </div>
    </div>

    <form wire:submit.prevent="submit" class="space-y-6">
        <!-- Bank Accounts Selection -->
        <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-4">
            <label class="block text-sm font-semibold text-white">
                {{ __('Select Destination Bank Account') }} <span class="text-rose-400">*</span>
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($bankAccounts as $bank)
                    <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all {{ $bankAccountId === $bank->id ? 'bg-emerald-950/30 border-emerald-500 shadow-sm shadow-emerald-500/10' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-white">{{ app()->getLocale() === 'ar' ? $bank->bankNameAr : $bank->bankNameEn }}</span>
                            <input type="radio" wire:model.live="bankAccountId" value="{{ $bank->id }}" class="text-emerald-500 focus:ring-emerald-400">
                        </div>
                        <div class="text-xs text-slate-400 space-y-1">
                            <div><span class="text-slate-500">{{ __('Account No:') }}</span> <span class="font-mono text-slate-200">{{ $bank->accountNumber }}</span></div>
                            <div><span class="text-slate-500">{{ __('Beneficiary:') }}</span> <span class="text-slate-300">{{ $bank->beneficiaryName }}</span></div>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('bankAccountId')
                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Top-up Amount & Reference -->
        <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="amount" class="block text-sm font-semibold text-white mb-2">
                        {{ __('Transferred Amount (SDG)') }} <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <input type="number" step="0.01" min="5000" id="amount" wire:model.defer="amount" placeholder="5000.00"
                            class="block w-full rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 px-4 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs font-semibold text-slate-400">
                            SDG
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5">{{ __('Minimum top-up amount is 5,000.00 SDG.') }}</p>
                    @error('amount')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reference" class="block text-sm font-semibold text-white mb-2">
                        {{ __('Transaction Reference Number') }} <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="reference" wire:model.defer="reference" placeholder="e.g. TRX-987654"
                        class="block w-full rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 px-4 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm font-mono">
                    <p class="text-xs text-slate-500 mt-1.5">{{ __('Enter the bank transfer reference or transaction ID exactly as shown on receipt.') }}</p>
                    @error('reference')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Receipt Upload -->
            <div>
                <label class="block text-sm font-semibold text-white mb-2">
                    {{ __('Transfer Receipt Document') }} <span class="text-rose-400">*</span>
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-800 border-dashed rounded-xl bg-slate-950/40 hover:border-slate-700 transition-colors">
                    <div class="space-y-2 text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-xs text-slate-400 justify-center">
                            <label for="receipt" class="relative cursor-pointer rounded-md font-semibold text-emerald-400 hover:text-emerald-300">
                                <span>{{ __('Upload a file') }}</span>
                                <input id="receipt" type="file" wire:model="receipt" class="sr-only" accept=".jpg,.jpeg,.png,.webp,.pdf">
                            </label>
                            <p class="pl-1">{{ __('or drag and drop') }}</p>
                        </div>
                        <p class="text-[11px] text-slate-500">{{ __('PNG, JPG, WEBP, or PDF up to 10MB') }}</p>
                    </div>
                </div>

                <div wire:loading wire:target="receipt" class="mt-2 text-xs text-emerald-400">
                    {{ __('Uploading receipt...') }}
                </div>

                @if ($receipt)
                    <div class="mt-2 text-xs text-emerald-400 flex items-center gap-2">
                        ✓ {{ __('File selected: ready to upload') }}
                    </div>
                @endif

                @error('receipt')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="/account/top-ups" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white text-xs font-semibold transition-colors">
                {{ __('Cancel') }}
            </a>
            <button type="submit" wire:loading.attr="disabled"
                class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20 transition-all disabled:opacity-50">
                <span wire:loading.remove wire:target="submit">{{ __('Submit Top-Up Request') }}</span>
                <span wire:loading wire:target="submit">{{ __('Submitting...') }}</span>
            </button>
        </div>
    </form>
</div>
