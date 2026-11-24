@props(['class' => 'h-10 w-10', 'title' => null, 'tone' => 'cobalt'])

@php
    $stroke = $tone === 'light' ? '#ffffff' : '#1F4FD8';
@endphp

<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" role="img" aria-label="{{ $title ?? __('sitecheck.app.name') }}">
    {{-- Magnifier lens --}}
    <circle cx="22" cy="22" r="13" stroke="{{ $stroke }}" stroke-width="2.2" />
    {{-- Handle --}}
    <path d="M31.6 31.6 42 42" stroke="{{ $stroke }}" stroke-width="2.2" stroke-linecap="round" />
    {{-- Checkmark-shaped heartbeat inside the lens --}}
    <path
        d="M11.5 20.5h3.2l1.7-5 3 9.6 1.6-4.6h2.4l2.6-3 3.2 6.2"
        stroke="{{ $stroke }}"
        stroke-width="2.4"
        stroke-linecap="round"
        stroke-linejoin="round"
    />
</svg>
