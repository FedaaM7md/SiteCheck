{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@extends('layouts.app')

@section('title', __('sitecheck.report.for', ['host' => $scan->host]))

@section('content')
    @php
        $target = $report['target'];
        $priorities = [];
        foreach ($report['sections'] as $section) {
            foreach ($section['findings'] as $finding) {
                if ($finding['severity'] === 'fail' && ! empty($finding['fix'])) {
                    $priorities[] = ['section' => $section['title'], 'finding' => $finding];
                }
            }
        }
        $priorities = array_slice($priorities, 0, 3);
        $scannedAt = $scan->scanned_at?->translatedFormat('j F Y · H:i') ?? $scan->created_at?->translatedFormat('j F Y · H:i');
        $overallTone = in_array($report['overall']['tone'], ['excellent', 'good'], true) ? 'good' : $report['overall']['tone'];
    @endphp

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <a href="{{ route('history.index') }}" class="tap-link inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">
                    <x-icon name="arrow" class="h-4 w-4 rotate-180 rtl:rotate-0" />
                    {{ __('sitecheck.report.back_history') }}
                </a>
                <p class="mt-3 section-label">{{ __('sitecheck.report.title') }}</p>
                <h1 class="mt-1 break-all font-mono text-3xl font-semibold tracking-tight text-ink" dir="ltr">{{ $scan->host }}</h1>
                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-soft">
                    <a href="{{ $scan->url }}" target="_blank" rel="noopener noreferrer" class="tap-link link break-all font-mono text-xs" dir="ltr">{{ $scan->url }}</a>
                    <span class="hidden h-1 w-1 rounded-full bg-line-strong sm:inline-block"></span>
                    <span>{{ __('sitecheck.report.scanned_at') }}: {{ $scannedAt }}</span>
                    <span class="hidden h-1 w-1 rounded-full bg-line-strong sm:inline-block"></span>
                    <span>{{ __('sitecheck.report.duration') }}: <span class="font-mono tabular-nums">{{ number_format($report['duration_ms'] / 1000, 1) }}</span>s</span>
                </p>
                @if ($scan->isDemo())
                    <p class="mt-2 inline-flex items-center gap-1.5 rounded border border-line bg-report px-2.5 py-1 text-xs font-semibold text-soft">
                        <x-icon name="info" class="h-3.5 w-3.5 text-brand-600" />
                        {{ __('sitecheck.report.sample_note') }}
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2 no-print">
                <a href="{{ route('scans.print', $scan) }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">
                    <x-icon name="print" class="h-4 w-4" />
                    {{ __('sitecheck.report.print') }}
                </a>
                <a href="{{ route('scans.export', $scan) }}" class="btn-secondary btn-sm">
                    <x-icon name="download" class="h-4 w-4" />
                    {{ __('sitecheck.report.export') }}
                </a>
                @if (! $scan->isDemo())
                    <form method="POST" action="{{ route('scans.rescan', $scan) }}">
                        @csrf
                        <button type="submit" class="btn-primary btn-sm">
                            <x-icon name="refresh" class="h-4 w-4" />
                            {{ __('sitecheck.report.rescan') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('scans.destroy', $scan) }}" data-confirm="{{ __('sitecheck.report.delete_confirm') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm">
                            <x-icon name="trash" class="h-4 w-4" />
                            {{ __('sitecheck.report.delete') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <section class="panel mt-6" aria-labelledby="overall-heading">
            <div class="border-b border-line bg-report px-5 py-2.5">
                <span class="section-label">{{ __('sitecheck.report.summary') }}</span>
            </div>
            <div class="grid items-center gap-6 p-5 lg:grid-cols-[auto_auto_1fr_auto]">
                <div class="justify-self-center">
                    <x-score-ring :score="$report['overall']['score']" :tone="$report['overall']['tone']" size="lg" />
                </div>

                <div class="justify-self-center">
                    <span class="grade-stamp grade-stamp--{{ $overallTone }}" role="img" aria-label="{{ __('sitecheck.report.grade') }} {{ $report['overall']['grade'] }}">{{ $report['overall']['grade'] }}</span>
                </div>

                <div class="text-center lg:text-start">
                    <div class="flex flex-wrap items-center justify-center gap-2 lg:justify-start">
                        <x-tone-badge :tone="$report['overall']['tone']" :label="$report['overall']['label']" />
                        <span class="text-xs font-semibold text-soft">{{ __('sitecheck.report.grade') }} {{ $report['overall']['grade'] }}</span>
                    </div>
                    <h2 id="overall-heading" class="mt-2 text-xl font-bold text-ink">{{ __('sitecheck.report.overall') }}</h2>
                    <p class="mt-1 max-w-xl text-sm leading-relaxed text-soft">{{ $report['overall']['summary'] }}</p>
                    <p class="mt-2 text-xs text-faint">{{ __('sitecheck.report.overall_summary') }}</p>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded border border-pass/30 bg-pass-soft px-3 py-2">
                        <p class="font-mono text-2xl font-semibold tabular-nums text-pass" dir="ltr">{{ $report['totals']['pass'] }}</p>
                        <p class="text-[0.6rem] font-semibold text-soft">{{ __('sitecheck.report.checks_passed') }}</p>
                    </div>
                    <div class="rounded border border-warn/30 bg-warn-soft px-3 py-2">
                        <p class="font-mono text-2xl font-semibold tabular-nums text-warn" dir="ltr">{{ $report['totals']['warn'] }}</p>
                        <p class="text-[0.6rem] font-semibold text-soft">{{ __('sitecheck.report.warnings') }}</p>
                    </div>
                    <div class="rounded border border-fail/30 bg-fail-soft px-3 py-2">
                        <p class="font-mono text-2xl font-semibold tabular-nums text-fail" dir="ltr">{{ $report['totals']['fail'] }}</p>
                        <p class="text-[0.6rem] font-semibold text-soft">{{ __('sitecheck.report.failures') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <nav class="mt-6 flex flex-wrap gap-2 no-print" aria-label="{{ __('sitecheck.report.sections') }}">
            @foreach ($report['sections'] as $section)
                @php
                    $chip = in_array($section['tone'], ['excellent', 'good'], true) ? 'chip-pass' : ($section['tone'] === 'fair' ? 'chip-warn' : 'chip-fail');
                @endphp
                <a href="#section-{{ $section['key'] }}" class="tap-link inline-flex items-center gap-2 rounded border border-line bg-white px-3 py-1.5 text-xs font-bold text-soft hover:border-brand-400 hover:text-brand-700">
                    {{ $section['title'] }}
                    <span class="{{ $chip }} tabular-nums">{{ $section['score'] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($priorities !== [])
            <section class="panel mt-6" aria-labelledby="priorities-heading">
                <div class="flex items-center gap-2 border-b border-line bg-report px-5 py-2.5">
                    <x-icon name="alert" class="h-4 w-4 text-fail" />
                    <h2 id="priorities-heading" class="section-label">{{ __('sitecheck.report.priorities') }}</h2>
                </div>
                <ol class="grid gap-0 divide-y divide-line md:grid-cols-3 md:divide-x md:divide-y-0">
                    @foreach ($priorities as $priority)
                        <li class="p-4">
                            <p class="text-[0.65rem] font-semibold text-faint">{{ $priority['section'] }}</p>
                            <p class="mt-1 text-sm font-bold text-ink">{{ $priority['finding']['title'] }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-soft">{{ $priority['finding']['fix'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <div class="mt-6">
            @include('partials.report-sections', ['report' => $report, 'printMode' => false])
        </div>

        <section class="panel mt-6" aria-labelledby="meta-heading">
            <div class="border-b border-line bg-report px-5 py-2.5">
                <h2 id="meta-heading" class="section-label">{{ __('sitecheck.report.raw_meta') }}</h2>
            </div>
            <div class="p-5">
                <dl class="kv">
                    <div>
                        <dt>{{ __('sitecheck.report.target') }}</dt>
                        <dd dir="ltr" class="font-mono text-xs">{{ $target['input'] ?? $scan->input }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('sitecheck.report.pinned_ip') }}</dt>
                        <dd dir="ltr" class="font-mono text-xs">{{ $report['meta']['pinned_ip'] ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt>{{ __('sitecheck.report.resolved_ips') }}</dt>
                        <dd dir="ltr" class="font-mono text-xs">{{ implode(', ', $report['meta']['resolved_ips'] ?? []) ?: '—' }}</dd>
                    </div>
                </dl>
                <p class="mt-3 text-xs text-faint">{{ __('sitecheck.report.scanned_by') }}</p>
            </div>
        </section>
    </div>
@endsection
