{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@extends('layouts.app')

@section('title', __('sitecheck.pages.'.$page.'.title'))
@section('meta_description', __('sitecheck.pages.'.$page.'.lead'))

@section('content')
    @php
        $content = __('sitecheck.pages.'.$page);
    @endphp

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <p class="section-label">{{ __('sitecheck.pages.kicker') }}</p>
        <h1 class="mt-2 text-3xl font-bold text-ink sm:text-4xl">{{ $content['title'] }}</h1>
        <p class="mt-3 max-w-2xl text-base leading-relaxed text-soft">{{ $content['lead'] }}</p>

        @isset($content['updated'])
            <p class="mt-2 text-xs font-semibold text-faint">{{ $content['updated'] }}</p>
        @endisset

        @isset($content['blocks'])
            <div class="mt-8 space-y-4">
                @foreach ($content['blocks'] as $block)
                    <section class="panel p-5">
                        <h2 class="text-base font-bold text-ink">{{ $block['heading'] }}</h2>
                        <p class="mt-1.5 text-sm leading-relaxed text-soft">{{ $block['body'] }}</p>
                    </section>
                @endforeach
            </div>
        @endisset

        @isset($content['items'])
            <div class="mt-8 space-y-2">
                @foreach ($content['items'] as $item)
                    <details class="panel px-4 py-2">
                        <summary class="min-h-11 cursor-pointer select-none py-2 text-sm font-semibold text-ink">{{ $item['q'] }}</summary>
                        <p class="pb-2 text-sm leading-relaxed text-soft">{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </div>
        @endisset

        @isset($content['releases'])
            <div class="mt-8 space-y-4">
                @foreach ($content['releases'] as $release)
                    <section class="panel p-5">
                        <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-line pb-3">
                            <h2 class="font-mono text-lg font-semibold text-ink" dir="ltr">{{ $release['version'] }}</h2>
                            <span class="text-xs font-semibold text-faint">{{ $release['date'] }}</span>
                        </div>
                        <ul class="mt-3 space-y-1.5">
                            @foreach ($release['items'] as $item)
                                <li class="flex items-start gap-2 text-sm leading-relaxed text-soft">
                                    <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endisset

        <div class="mt-10 flex flex-wrap gap-2 border-t border-line pt-6">
            <a href="{{ route('home') }}#scan" class="btn-primary">{{ __('sitecheck.pages.cta_scan') }}</a>
            <a href="{{ route('pages.help') }}" class="btn-secondary">{{ __('sitecheck.nav.help') }}</a>
        </div>
    </div>
@endsection
