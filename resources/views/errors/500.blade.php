{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@extends('layouts.app')

@section('title', __('sitecheck.errors.500_title'))

@section('content')
    <div class="mx-auto flex max-w-3xl flex-col items-center px-4 py-20 text-center sm:px-6">
        <div class="flex h-14 w-14 items-center justify-center rounded border border-line bg-fail-soft text-fail">
            <x-icon name="alert" class="h-7 w-7" />
        </div>
        <p class="mt-6 font-mono text-xs font-semibold uppercase tracking-[0.2em] text-faint">500</p>
        <h1 class="mt-2 text-3xl font-bold text-ink">{{ __('sitecheck.errors.500_title') }}</h1>
        <p class="mt-3 max-w-lg text-sm leading-relaxed text-soft">{{ __('sitecheck.errors.500_body') }}</p>
        <div class="mt-7 flex flex-wrap justify-center gap-2">
            <a href="{{ route('home') }}" class="btn-primary">{{ __('sitecheck.errors.back_home') }}</a>
        </div>
    </div>
@endsection
