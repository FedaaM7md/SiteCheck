{{-- Copyright (c) 2026 Fedaa Mohamed. All rights reserved. --}}
@props(['tone' => 'good', 'label' => null])

@php
    $map = [
        'excellent' => 'chip-pass',
        'good' => 'chip-pass',
        'fair' => 'chip-warn',
        'poor' => 'chip-fail',
    ];
    $classes = $map[$tone] ?? $map['good'];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $label ?? __('sitecheck.tones.'.$tone) }}
</span>
