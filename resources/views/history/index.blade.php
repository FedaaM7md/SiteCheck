{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@extends('layouts.app')

@section('title', __('sitecheck.history.title'))

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="section-label">{{ __('sitecheck.report.checklist') }}</p>
                <h1 class="mt-1 text-3xl font-bold text-ink">{{ __('sitecheck.history.title') }}</h1>
                <p class="mt-1 text-sm text-soft">{{ __('sitecheck.history.subtitle') }}</p>
                <p class="mt-2 text-xs font-semibold text-faint">
                    {{ __('sitecheck.history.summary', ['count' => $scans->total()]) }}
                </p>
            </div>
            <a href="{{ route('home') }}#scan" class="btn-primary">
                <x-icon name="search" class="h-4 w-4" />
                {{ __('sitecheck.nav.new_scan') }}
            </a>
        </div>

        <button
            type="button"
            data-disclosure-toggle
            aria-expanded="{{ $query !== '' ? 'true' : 'false' }}"
            aria-controls="history-search"
            class="btn-secondary mt-4 w-full justify-between md:hidden"
        >
            <span class="inline-flex items-center gap-2">
                <x-icon name="search" class="h-4 w-4" />
                {{ __('sitecheck.history.search') }}
            </span>
            <x-icon name="arrow" class="h-4 w-4 rotate-90" />
        </button>

        <div id="history-search" class="mt-4 @if ($query === '') hidden @endif md:block">
            <form method="GET" action="{{ route('history.index') }}" role="search" class="flex flex-col gap-2 sm:flex-row">
                <div class="flex-1">
                    <label for="q" class="sr-only">{{ __('sitecheck.history.search_label') }}</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $query }}"
                        class="input"
                        placeholder="{{ __('sitecheck.history.search_placeholder') }}"
                        dir="auto"
                        autocomplete="off"
                    >
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">
                        <x-icon name="search" class="h-4 w-4" />
                        {{ __('sitecheck.history.search') }}
                    </button>
                    @if ($query !== '')
                        <a href="{{ route('history.index') }}" class="btn-secondary">{{ __('sitecheck.history.clear') }}</a>
                    @endif
                </div>
            </form>
        </div>

        @if (! empty($hasDemo))
            <div class="panel-soft mt-4 flex items-start gap-2 px-4 py-2.5 text-xs leading-relaxed text-soft">
                <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                <span>{{ __('sitecheck.history.sample_notice') }}</span>
            </div>
        @endif

        @if ($scans->isEmpty())
            <div class="panel mt-6 px-6 py-16 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded border border-line bg-report text-soft">
                    <x-icon name="search" class="h-6 w-6" />
                </div>
                @if ($query !== '')
                    <h2 class="mt-4 text-lg font-bold text-ink">{{ __('sitecheck.history.empty_search_title') }}</h2>
                    <p class="mt-1 text-sm text-soft">{{ __('sitecheck.history.empty_search_body') }}</p>
                    <a href="{{ route('history.index') }}" class="btn-secondary mt-5">{{ __('sitecheck.history.clear') }}</a>
                @else
                    <h2 class="mt-4 text-lg font-bold text-ink">{{ __('sitecheck.history.empty_title') }}</h2>
                    <p class="mt-1 text-sm text-soft">{{ __('sitecheck.history.empty_body') }}</p>
                    <a href="{{ route('home') }}#scan" class="btn-primary mt-5">{{ __('sitecheck.form.submit') }}</a>
                @endif
            </div>
        @else
            <div class="panel mt-6 hidden overflow-hidden md:block">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-line bg-report text-[0.7rem] font-semibold text-soft">
                            <th scope="col" class="px-5 py-3 text-start">{{ __('sitecheck.history.target') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('sitecheck.history.score') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('sitecheck.history.grade') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('sitecheck.history.date') }}</th>
                            <th scope="col" class="px-5 py-3 text-end">{{ __('sitecheck.history.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($scans as $scan)
                            <tr class="border-b border-line/70 last:border-0 hover:bg-report/70">
                                <td class="px-5 py-4">
                                    <a href="{{ route('scans.show', $scan) }}" class="block min-w-0">
                                        <span class="block truncate font-mono text-sm font-semibold text-ink" dir="ltr">{{ $scan->host }}</span>
                                        <span class="block max-w-xs truncate font-mono text-xs text-faint" dir="ltr">{{ $scan->url }}</span>
                                    </a>
                                    @if ($scan->isDemo())
                                        <span class="chip-neutral mt-1">{{ __('sitecheck.history.sample_chip') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-baseline gap-1.5">
                                        <span class="font-mono text-lg font-semibold tabular-nums text-ink" dir="ltr">{{ $scan->overall_score }}</span>
                                        <x-tone-badge :tone="$scan->scoreTone()" />
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="grade-stamp grade-stamp--{{ in_array($scan->scoreTone(), ['excellent', 'good'], true) ? 'good' : $scan->scoreTone() }} h-9 w-9 text-base">{{ $scan->grade }}</span>
                                </td>
                                <td class="px-4 py-4 text-xs text-soft">
                                    {{ ($scan->scanned_at ?? $scan->created_at)?->translatedFormat('j M Y') }}<br>
                                    <span class="text-faint" dir="ltr">{{ ($scan->scanned_at ?? $scan->created_at)?->format('H:i') }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('scans.show', $scan) }}" class="btn-ghost btn-sm">{{ __('sitecheck.history.view') }}</a>
                                        @if (! $scan->isDemo())
                                            <form method="POST" action="{{ route('scans.rescan', $scan) }}">
                                                @csrf
                                                <button type="submit" class="btn-secondary btn-sm" aria-label="{{ __('sitecheck.history.rescan') }} {{ $scan->host }}">
                                                    <x-icon name="refresh" class="h-3.5 w-3.5" />
                                                    <span class="hidden lg:inline">{{ __('sitecheck.history.rescan') }}</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('scans.destroy', $scan) }}" data-confirm="{{ __('sitecheck.history.delete_confirm') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-ghost btn-sm text-fail hover:bg-fail-soft" aria-label="{{ __('sitecheck.history.delete') }} {{ $scan->host }}">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 space-y-3 md:hidden">
                @foreach ($scans as $scan)
                    <article class="panel p-4">
                        <div class="flex items-start gap-3">
                            <span class="grade-stamp grade-stamp--{{ in_array($scan->scoreTone(), ['excellent', 'good'], true) ? 'good' : $scan->scoreTone() }} h-10 w-10 text-lg">{{ $scan->grade }}</span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('scans.show', $scan) }}" class="flex min-h-11 items-center font-mono text-sm font-semibold text-ink" dir="ltr"><span class="truncate">{{ $scan->host }}</span></a>
                                <p class="mt-0.5 text-xs text-soft">{{ ($scan->scanned_at ?? $scan->created_at)?->translatedFormat('j M Y · H:i') }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-sm font-semibold tabular-nums text-ink" dir="ltr">{{ $scan->overall_score }}</span>
                                    <x-tone-badge :tone="$scan->scoreTone()" />
                                    @if ($scan->isDemo())
                                        <span class="chip-neutral">{{ __('sitecheck.history.sample_chip') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-2 border-t border-line pt-3">
                            <a href="{{ route('scans.show', $scan) }}" class="btn-secondary btn-sm flex-1">{{ __('sitecheck.history.view') }}</a>
                            @if (! $scan->isDemo())
                                <form method="POST" action="{{ route('scans.rescan', $scan) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary btn-sm" aria-label="{{ __('sitecheck.history.rescan') }} {{ $scan->host }}">
                                        <x-icon name="refresh" class="h-4 w-4" />
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('scans.destroy', $scan) }}" data-confirm="{{ __('sitecheck.history.delete_confirm') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost btn-sm text-fail hover:bg-fail-soft" aria-label="{{ __('sitecheck.history.delete') }} {{ $scan->host }}">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @include('partials.pagination', ['paginator' => $scans])
        @endif
    </div>
@endsection
