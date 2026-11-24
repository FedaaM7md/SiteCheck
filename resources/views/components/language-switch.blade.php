{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@props(['class' => ''])

<form method="POST" action="{{ route('locale.update') }}" class="{{ $class }}" aria-label="{{ __('sitecheck.nav.language') }}">
    @csrf
    <div class="inline-flex items-center gap-0.5 rounded border border-line bg-report p-0.5">
        @foreach (['ar' => 'arabic', 'en' => 'english'] as $code => $labelKey)
            <button
                type="submit"
                name="locale"
                value="{{ $code }}"
                lang="{{ $code }}"
                dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}"
                class="lang-btn rounded px-2.5 py-1 text-[0.7rem] font-semibold transition {{ app()->getLocale() === $code ? 'bg-white text-brand-700 shadow-sm' : 'text-soft hover:text-ink' }}"
                @if (app()->getLocale() === $code) aria-current="true" @endif
            >
                {{ __('sitecheck.nav.'.$labelKey) }}
            </button>
        @endforeach
    </div>
</form>
