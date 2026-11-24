{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@props(['prefill' => '', 'compact' => false])

@php
    $hasDkimError = $errors->has('dkim_selectors');
@endphp

<form method="POST" action="{{ route('scan.store') }}" data-scan class="w-full">
    @csrf

    @if ($errors->any())
        <div class="alert-error mb-4" role="alert">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="font-bold">{{ __('sitecheck.form.errors_title') }}</p>
                <ul class="mt-1 list-disc space-y-0.5 ps-4 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div>
        <label for="target" class="label">{{ __('sitecheck.form.target_label') }}</label>
        <div class="flex flex-col gap-2 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-faint">
                    <x-icon name="globe" class="h-5 w-5" />
                </span>
                <input
                    id="target"
                    name="target"
                    type="text"
                    value="{{ old('target', $prefill) }}"
                    required
                    dir="ltr"
                    autocomplete="url"
                    autocapitalize="off"
                    spellcheck="false"
                    inputmode="url"
                    class="input ps-10 text-start @error('target') input-error @enderror"
                    placeholder="{{ __('sitecheck.form.target_placeholder') }}"
                    aria-describedby="target-help @error('target') target-error @enderror"
                    @error('target') aria-invalid="true" @enderror
                >
            </div>

            <button type="submit" data-submit class="btn-primary shrink-0 justify-center sm:w-auto">
                <span data-idle class="inline-flex items-center gap-2">
                    <x-icon name="search" class="h-4 w-4" />
                    {{ __('sitecheck.form.submit') }}
                </span>
                <span data-busy hidden class="inline-flex items-center gap-2">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    {{ __('sitecheck.form.submitting') }}
                </span>
            </button>
        </div>
        <p id="target-help" class="help">{{ __('sitecheck.form.target_help') }}</p>
        @error('target')
            <p id="target-error" class="field-error" role="alert">
                <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ $message }}</span>
            </p>
        @enderror
    </div>

    <details class="mt-4 rounded border border-line bg-report px-4 py-3" @if ($hasDkimError) open @endif>
        <summary class="min-h-11 cursor-pointer select-none py-2 text-sm font-semibold text-soft">
            {{ __('sitecheck.form.advanced') }}
            <span class="ms-1 text-xs font-normal text-faint">({{ __('sitecheck.form.optional') }})</span>
        </summary>
        <div class="mt-3">
            <label for="dkim_selectors" class="label">{{ __('sitecheck.form.dkim_label') }}</label>
            <input
                id="dkim_selectors"
                name="dkim_selectors"
                type="text"
                value="{{ old('dkim_selectors') }}"
                dir="ltr"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false"
                class="input font-mono @error('dkim_selectors') input-error @enderror"
                placeholder="{{ __('sitecheck.form.dkim_placeholder') }}"
                aria-describedby="dkim-help @error('dkim_selectors') dkim-error @enderror"
                @error('dkim_selectors') aria-invalid="true" @enderror
            >
            <p id="dkim-help" class="help">{{ __('sitecheck.form.dkim_help') }}</p>
            @error('dkim_selectors')
                <p id="dkim-error" class="field-error" role="alert">
                    <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>
    </details>

    <p class="help">{{ __('sitecheck.form.note') }}</p>
</form>
