<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 space-y-6">
    <div>
        <a href="/account/orders/{{ $orderId }}" class="inline-flex items-center text-xs font-semibold text-slate-400 hover:text-white transition-colors mb-2">
            ← {{ __('Back to Order') }}
        </a>
        <h1 class="text-2xl font-bold text-white">{{ __('Required Customer Action') }}</h1>
        <p class="text-xs text-slate-400 mt-1">{{ __('The fulfillment operations team has requested clarification or updated documentation to proceed with your order.') }}</p>
    </div>

    <!-- Request Prompt Box -->
    <div class="p-6 rounded-2xl bg-amber-950/40 border border-amber-500/40 space-y-2">
        <span class="px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-bold text-[10px] uppercase tracking-wider">
            {{ __('Instructions from Staff') }}
        </span>
        <p class="text-base font-semibold text-white">
            {{ app()->getLocale() === 'ar' ? $descriptionAr : $descriptionEn }}
        </p>
        @if ($dueAt)
            <p class="text-xs text-amber-400/90 font-mono">
                {{ __('Deadline: :date', ['date' => $dueAt]) }}
            </p>
        @endif
    </div>

    <form wire:submit.prevent="respond" class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-6">
        <!-- Message -->
        <div>
            <label for="message" class="block text-sm font-semibold text-white mb-2">
                {{ __('Your Response Message') }} @if (! $requiredDocumentPurpose) <span class="text-rose-400">*</span> @endif
            </label>
            <textarea id="message" wire:model.defer="message" rows="4" placeholder="{{ __('Type your clarification or reply here...') }}"
                class="block w-full rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 px-4 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"></textarea>
            @error('message')
                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Optional or Required Document -->
        @if ($requiredDocumentPurpose)
            <div>
                <label class="block text-sm font-semibold text-white mb-2">
                    {{ __('Upload Requested Document') }} <span class="text-rose-400">*</span>
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-800 border-dashed rounded-xl bg-slate-950/40 hover:border-slate-700 transition-colors">
                    <div class="space-y-2 text-center">
                        <div class="flex text-xs text-slate-400 justify-center">
                            <label for="document" class="relative cursor-pointer rounded-md font-semibold text-emerald-400 hover:text-emerald-300">
                                <span>{{ __('Select document file') }}</span>
                                <input id="document" type="file" wire:model="document" class="sr-only" accept=".jpg,.jpeg,.png,.webp,.pdf">
                            </label>
                        </div>
                        <p class="text-[11px] text-slate-500">{{ __('PNG, JPG, WEBP, or PDF up to 10MB') }}</p>
                    </div>
                </div>

                @if ($document)
                    <div class="mt-2 text-xs text-emerald-400">
                        ✓ {{ __('File ready for upload') }}
                    </div>
                @endif

                @error('document')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-800">
            <a href="/account/orders/{{ $orderId }}" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white text-xs font-semibold transition-colors">
                {{ __('Cancel') }}
            </a>
            <button type="submit" wire:loading.attr="disabled"
                class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20 transition-all disabled:opacity-50">
                <span wire:loading.remove wire:target="respond">{{ __('Submit Response') }}</span>
                <span wire:loading wire:target="respond">{{ __('Submitting...') }}</span>
            </button>
        </div>
    </form>
</div>
