{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@extends('layouts.print')

@section('title', __('sitecheck.report.for', ['host' => $scan->host]))

@section('content')
    @php
        $overallTone = in_array($report['overall']['tone'], ['excellent', 'good'], true) ? 'good' : $report['overall']['tone'];
    @endphp

    <section class="mb-6">
        <p class="section-label">{{ __('sitecheck.report.title') }}</p>
        <h1 class="mt-1 break-all font-mono text-2xl font-semibold text-ink" dir="ltr">{{ $scan->host }}</h1>
        <p class="mt-1 font-mono text-sm text-soft" dir="ltr">{{ $scan->url }}</p>
        <p class="mt-1 text-xs text-soft">
            {{ __('sitecheck.report.scanned_at') }}: {{ $scan->scanned_at?->translatedFormat('j F Y · H:i') }}
            · {{ __('sitecheck.report.duration') }}: {{ number_format($report['duration_ms'] / 1000, 1) }}s
        </p>
    </section>

    <section class="mb-6 flex items-center gap-5 rounded border border-line-strong p-5 print-block">
        <x-score-ring :score="$report['overall']['score']" :tone="$report['overall']['tone']" />
        <span class="grade-stamp grade-stamp--{{ $overallTone }}">{{ $report['overall']['grade'] }}</span>
        <div>
            <p class="text-xl font-bold text-ink">{{ $report['overall']['label'] }} · {{ $report['overall']['grade'] }}</p>
            <p class="mt-1 text-sm text-soft">{{ $report['overall']['summary'] }}</p>
            <p class="mt-2 text-xs font-semibold text-soft">
                {{ $report['totals']['pass'] }} {{ __('sitecheck.report.checks_passed') }} ·
                {{ $report['totals']['warn'] }} {{ __('sitecheck.report.warnings') }} ·
                {{ $report['totals']['fail'] }} {{ __('sitecheck.report.failures') }}
            </p>
        </div>
    </section>

    @include('partials.report-sections', ['report' => $report, 'printMode' => true])

    <section class="mt-6 rounded border border-line-strong p-4 print-block">
        <h2 class="section-label">{{ __('sitecheck.report.raw_meta') }}</h2>
        <p class="mt-2 font-mono text-xs text-soft" dir="ltr">
            {{ $report['target']['input'] ?? $scan->input }}
            · IP: {{ $report['meta']['pinned_ip'] ?? '—' }}
        </p>
        <p class="mt-1 text-xs text-faint">{{ __('sitecheck.report.scanned_by') }}</p>
    </section>
@endsection
