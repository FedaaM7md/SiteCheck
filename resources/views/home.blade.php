{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="grid items-start gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:gap-12">
            <div>
                <p class="section-label">{{ __('sitecheck.home.kicker') }}</p>
                <h1 class="mt-2 text-3xl font-bold text-ink sm:text-4xl">{{ __('sitecheck.home.title') }}</h1>
                <p class="mt-3 max-w-xl text-base leading-relaxed text-soft">{{ __('sitecheck.home.subtitle') }}</p>

                <div id="scan" class="scan-console mt-6 scroll-mt-24 p-5 sm:p-6">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-line pb-3">
                        <span class="section-label">{{ __('sitecheck.home.form_label') }}</span>
                        <span dir="ltr" class="font-mono text-[0.68rem] tracking-wide text-faint">DNS · TLS · HTTP · MAIL · SEO</span>
                    </div>

                    @include('partials.scan-form', ['prefill' => $prefill])
                </div>
            </div>

            <aside class="panel-soft p-4 sm:p-5" aria-labelledby="requisition-heading">
                <h2 id="requisition-heading" class="section-label">{{ __('sitecheck.home.what_title') }}</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-soft">{{ __('sitecheck.home.what_body') }}</p>

                <dl class="mt-4 divide-y divide-line border-t border-line">
                    @foreach (['dns' => 'DNS', 'ssl' => 'TLS', 'email' => 'MAIL', 'http' => 'HTTP', 'seo' => 'SEO'] as $key => $code)
                        <div class="flex items-baseline gap-3 py-1.5 sm:py-2.5">
                            <dt class="w-11 shrink-0 font-mono text-[0.68rem] font-semibold uppercase tracking-wide text-faint" dir="ltr">{{ $code }}</dt>
                            <dd class="min-w-0">
                                <span class="block text-sm font-semibold text-ink">{{ __('sitecheck.sections.'.$key.'.title') }}</span>
                                <span class="mt-0.5 hidden text-xs leading-relaxed text-soft sm:block">{{ __('sitecheck.sections.'.$key.'.description') }}</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>

                <p class="mt-4 border-t border-line pt-3 text-xs leading-relaxed text-soft">{{ __('sitecheck.home.safety_note') }}</p>
            </aside>
        </div>

        @if ($recent->isNotEmpty())
            <section class="mt-12" aria-labelledby="recent-heading">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="recent-heading" class="text-lg font-bold text-ink">{{ __('sitecheck.home.recent_title') }}</h2>
                        <p class="mt-0.5 text-xs text-soft">{{ config('sitecheck.demo_mode') ? __('sitecheck.home.recent_note_demo') : __('sitecheck.home.recent_note') }}</p>
                    </div>
                    <a href="{{ route('history.index') }}" class="btn-secondary btn-sm">
                        {{ __('sitecheck.home.view_history') }}
                        <span class="font-mono tabular-nums" dir="ltr">({{ $total }})</span>
                        <x-icon name="arrow" class="h-3.5 w-3.5 rtl:rotate-180" />
                    </a>
                </div>

                <div class="panel mt-4 divide-y divide-line">
                    @foreach ($recent as $scan)
                        <a href="{{ route('scans.show', $scan) }}" class="flex items-center gap-4 px-4 py-3 transition hover:bg-report/70">
                            <span class="grade-stamp grade-stamp--{{ $scan->scoreTone() }} h-10 w-10 text-xl" aria-hidden="true">{{ $scan->grade }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-mono text-sm font-semibold text-ink" dir="ltr">{{ $scan->host }}</p>
                                <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-soft">
                                    <span>{{ ($scan->scanned_at ?? $scan->created_at)?->translatedFormat('j M Y · H:i') }}</span>
                                    @if ($scan->isDemo())
                                        <span class="chip-neutral">{{ __('sitecheck.history.sample_chip') }}</span>
                                    @endif
                                </p>
                            </div>
                            <span class="shrink-0 font-mono text-sm font-semibold tabular-nums text-soft" dir="ltr">{{ $scan->overall_score }}<span class="text-faint">/100</span></span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
