{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@php
    $printMode = $printMode ?? false;
    $severityStyles = [
        'pass' => ['icon' => 'check', 'class' => 'bg-pass-soft text-pass'],
        'warn' => ['icon' => 'alert', 'class' => 'bg-warn-soft text-warn'],
        'fail' => ['icon' => 'x', 'class' => 'bg-fail-soft text-fail'],
        'info' => ['icon' => 'info', 'class' => 'bg-info-soft text-info'],
    ];
    $sectionIcons = [
        'dns' => 'globe',
        'ssl' => 'lock',
        'email' => 'mail',
        'http' => 'shield',
        'seo' => 'search',
    ];
@endphp

<div class="space-y-6">
    @foreach ($report['sections'] as $section)
        <section id="section-{{ $section['key'] }}" class="panel anchor-target print-block overflow-hidden" aria-labelledby="section-{{ $section['key'] }}-title">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line bg-report px-5 py-4">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded border border-line bg-white text-brand-600">
                        <x-icon :name="$sectionIcons[$section['key']] ?? 'info'" class="h-4.5 w-4.5" />
                    </div>
                    <div class="min-w-0">
                        <h2 id="section-{{ $section['key'] }}-title" class="text-lg font-bold text-ink">{{ $section['title'] }}</h2>
                        <p class="mt-0.5 text-sm leading-relaxed text-soft">{{ $section['description'] }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[0.68rem] font-semibold text-soft">
                            <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-pass"></span>{{ $section['counts']['pass'] }} {{ __('sitecheck.report.checks_passed') }}</span>
                            <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-warn"></span>{{ $section['counts']['warn'] }} {{ __('sitecheck.report.warnings') }}</span>
                            <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-fail"></span>{{ $section['counts']['fail'] }} {{ __('sitecheck.report.failures') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-score-ring :score="$section['score']" :tone="$section['tone']" size="sm" />
                    <div>
                        <p class="text-[0.62rem] font-semibold text-faint">{{ __('sitecheck.report.grade') }}</p>
                        <p class="font-mono text-xl font-semibold {{ 'tone-'.$section['tone'] }}">{{ $section['grade'] }}</p>
                    </div>
                </div>
            </div>

            @if ($section['error'])
                <div class="px-5 pt-4">
                    <div class="alert-warn" role="status">
                        <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
                        <span>{{ $section['error'] }}</span>
                    </div>
                </div>
            @endif

            <ul class="divide-y divide-line/70">
                @forelse ($section['findings'] as $finding)
                    @php $style = $severityStyles[$finding['severity']] ?? $severityStyles['info']; @endphp
                    <li class="finding">
                        <span class="finding-icon {{ $style['class'] }}">
                            <x-icon :name="$style['icon']" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="finding-title">{{ $finding['title'] }}</p>
                                <span class="chip-{{ $finding['severity'] }}">{{ $finding['severity_label'] }}</span>
                            </div>
                            <p class="finding-detail">{{ $finding['detail'] }}</p>
                            @if (! empty($finding['fix']))
                                <div class="finding-fix">
                                    <span class="font-bold text-ink">{{ __('sitecheck.report.fix') }}:</span>
                                    {{ $finding['fix'] }}
                                </div>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-sm text-soft">{{ __('sitecheck.report.no_issues') }}</li>
                @endforelse
            </ul>

            <details class="border-t border-line" @if ($printMode) open @endif>
                <summary class="cursor-pointer select-none px-5 py-3 text-xs font-semibold text-soft hover:bg-report">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="code" class="h-4 w-4" />
                        {{ __('sitecheck.report.details') }}
                    </span>
                </summary>
                <div class="border-t border-line bg-report px-5 py-5">
                    @include('partials.evidence', ['section' => $section])
                </div>
            </details>
        </section>
    @endforeach
</div>
