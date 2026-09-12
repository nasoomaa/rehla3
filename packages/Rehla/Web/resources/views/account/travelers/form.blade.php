@extends('rehla-web::account.layout')

@section('account_content')
<div class="rounded-3xl bg-slate-900/50 border border-slate-800 p-8 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">
                {{ isset($traveler) ? __('rehla-web::messages.edit') : __('rehla-web::messages.add_new') }} {{ __('rehla-web::messages.travelers') }}
            </h1>
        </div>
        <a href="/account/travelers" class="text-xs font-semibold text-slate-400 hover:text-white">
            &larr; {{ __('rehla-web::messages.back') }}
        </a>
    </div>

    <form action="{{ isset($traveler) ? '/account/travelers/' . $traveler->id : '/account/travelers' }}" method="POST" class="space-y-4 pt-4 border-t border-slate-800">
        @csrf
        @if (isset($traveler))
            @method('PUT')
        @endif

        <div>
            <label for="full_name" class="block text-xs font-semibold text-slate-300 mb-1.5">{{ __('rehla-web::messages.full_name') }}</label>
            <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $traveler->fullName ?? '') }}" required
                   class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-emerald-500">
            @error('full_name')
                <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="date_of_birth" class="block text-xs font-semibold text-slate-300 mb-1.5">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $traveler->dateOfBirth ?? '') }}" required
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-emerald-500">
                @error('date_of_birth')
                    <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="gender" class="block text-xs font-semibold text-slate-300 mb-1.5">Gender</label>
                <select id="gender" name="gender" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-emerald-500">
                    <option value="male" {{ old('gender', isset($traveler) ? $traveler->gender->value : '') === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender', isset($traveler) ? $traveler->gender->value : '') === 'female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>
        </div>

        <div>
            <label for="passport_number" class="block text-xs font-semibold text-slate-300 mb-1.5">Passport Number</label>
            <input type="text" id="passport_number" name="passport_number" value="{{ old('passport_number', $traveler->passportNumber ?? '') }}" required
                   class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-emerald-500">
            @error('passport_number')
                <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="passport_issued_at" class="block text-xs font-semibold text-slate-300 mb-1.5">Passport Issued At</label>
                <input type="date" id="passport_issued_at" name="passport_issued_at" value="{{ old('passport_issued_at', $traveler->passportIssuedAt ?? '') }}" required
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-emerald-500">
                @error('passport_issued_at')
                    <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="passport_expires_at" class="block text-xs font-semibold text-slate-300 mb-1.5">Passport Expires At</label>
                <input type="date" id="passport_expires_at" name="passport_expires_at" value="{{ old('passport_expires_at', $traveler->passportExpiresAt ?? '') }}" required
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-emerald-500">
                @error('passport_expires_at')
                    <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="pt-4 flex items-center justify-end gap-3">
            <a href="/account/travelers" class="px-4 py-2 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition-colors">
                {{ __('rehla-web::messages.cancel') }}
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20 transition-all">
                {{ __('rehla-web::messages.save') }}
            </button>
        </div>
    </form>
</div>
@endsection
