@props(['name' => 'info', 'class' => 'h-5 w-5'])

<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="{{ $class }}" aria-hidden="true" focusable="false">
    @switch($name)
        @case('check')
            <path d="m5 12.5 4.2 4.2L19 6.8" />
            @break

        @case('alert')
            <path d="M12 3.6 21 20H3L12 3.6Z" />
            <path d="M12 9.5v4.2" />
            <path d="M12 17h.01" />
            @break

        @case('x')
            <path d="M6.5 6.5l11 11M17.5 6.5l-11 11" />
            @break

        @case('info')
            <circle cx="12" cy="12" r="8.6" />
            <path d="M12 11v5.2" />
            <path d="M12 8.2h.01" />
            @break

        @case('shield')
            <path d="M12 3.5 19.5 6.6v6.1c0 4.6-3 8.1-7.5 9.8-4.5-1.7-7.5-5.2-7.5-9.8V6.6L12 3.5Z" />
            <path d="m8.9 12 2.2 2.3 4-4.3" />
            @break

        @case('globe')
            <circle cx="12" cy="12" r="8.6" />
            <path d="M3.6 12h16.8M12 3.4c2.4 2.4 3.6 5.3 3.6 8.6s-1.2 6.2-3.6 8.6c-2.4-2.4-3.6-5.3-3.6-8.6S9.6 5.8 12 3.4Z" />
            @break

        @case('lock')
            <rect x="4.8" y="10.2" width="14.4" height="10.2" rx="2.2" />
            <path d="M8.4 10.2V8a3.6 3.6 0 0 1 7.2 0v2.2" />
            <path d="M12 14.2v2.4" />
            @break

        @case('mail')
            <rect x="3.2" y="5.6" width="17.6" height="12.8" rx="2.4" />
            <path d="m4.4 7.4 7.6 5.6 7.6-5.6" />
            @break

        @case('search')
            <circle cx="11" cy="11" r="6.5" />
            <path d="m16 16 4.2 4.2" />
            @break

        @case('chart')
            <path d="M4 20V10.5M10 20V4.5M16 20v-6.5M22 20H2" />
            @break

        @case('print')
            <path d="M7 9V4h10v5" />
            <rect x="4" y="9" width="16" height="8" rx="2" />
            <path d="M7 14h10v6H7z" />
            @break

        @case('download')
            <path d="M12 4v10.2" />
            <path d="m7.8 10.6 4.2 4.2 4.2-4.2" />
            <path d="M4.5 18.5h15" />
            @break

        @case('refresh')
            <path d="M19.4 9.2A8 8 0 1 0 20 14" />
            <path d="M20 4.5v5h-5" />
            @break

        @case('trash')
            <path d="M4.8 6.8h14.4M9.4 6.8V4.6h5.2v2.2M6.6 6.8l.9 12.6h9l.9-12.6" />
            <path d="M10.4 10.4v5.4M13.6 10.4v5.4" />
            @break

        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break

        @case('close')
            <path d="M6 6l12 12M18 6 6 18" />
            @break

        @case('clock')
            <circle cx="12" cy="12" r="8.6" />
            <path d="M12 7.6V12l3.2 2" />
            @break

        @case('arrow')
            <path d="M4.5 12h14" />
            <path d="m13 6.5 5.5 5.5-5.5 5.5" />
            @break

        @case('server')
            <rect x="3.6" y="4.4" width="16.8" height="6" rx="2" />
            <rect x="3.6" y="13.6" width="16.8" height="6" rx="2" />
            <path d="M7 7.4h.01M7 16.6h.01" />
            @break

        @case('link')
            <path d="M10 14a4 4 0 0 1 0-5.6l2.4-2.4a4 4 0 1 1 5.6 5.6L16.4 13" />
            <path d="M14 10a4 4 0 0 1 0 5.6l-2.4 2.4A4 4 0 0 1 6 12.4L7.6 11" />
            @break

        @case('code')
            <path d="m8 7-5 5 5 5M16 7l5 5-5 5M13.5 4.5l-3 15" />
            @break

        @default
            <circle cx="12" cy="12" r="8.6" />
            <path d="M12 11v5.2M12 8.2h.01" />
    @endswitch
</svg>
