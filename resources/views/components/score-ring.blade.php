{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@props(['score' => 0, 'tone' => 'good', 'size' => 'md'])

@php
    $score = max(0, min(100, (int) $score));
    $toneClass = in_array($tone, ['excellent', 'good', 'fair', 'poor'], true) ? $tone : 'good';
    $sizeClass = match ($size) {
        'sm' => 'score-ring--sm',
        'lg' => 'score-ring--lg',
        default => '',
    };
@endphp

<div
    {{ $attributes->merge(['class' => "score-ring {$sizeClass} ring-{$toneClass}"]) }}
    style="--score: {{ $score }}"
    role="img"
    aria-label="{{ __('sitecheck.report.score_of', ['score' => $score]) }}"
>
    <div class="text-center leading-none">
        <span dir="ltr" class="block font-mono font-semibold tabular-nums {{ $size === 'sm' ? 'text-base' : ($size === 'lg' ? 'text-5xl' : 'text-3xl') }} {{ 'tone-'.$toneClass }}">{{ $score }}</span>
        @if ($size !== 'sm')
            <span class="mt-1.5 block text-[0.6rem] font-semibold text-soft">{{ __('sitecheck.report.score') }}</span>
        @endif
    </div>
</div>
