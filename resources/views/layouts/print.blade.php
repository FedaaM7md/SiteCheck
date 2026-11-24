<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ __('sitecheck.app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans antialiased">
    <div class="mx-auto max-w-4xl px-6 py-8 print:max-w-none print:px-0 print:py-0">
        <header class="mb-8 flex items-start justify-between gap-4 border-b-2 border-brand-600 pb-4">
            <div class="flex items-center gap-3">
                <x-logo class="h-11 w-11" />
                <div>
                    <p class="text-lg font-bold tracking-tight text-ink">{{ __('sitecheck.app.name') }}</p>
                    <p class="text-[0.72rem] text-soft">{{ __('sitecheck.app.tagline') }}</p>
                </div>
            </div>
            <button type="button" data-print class="btn-secondary btn-sm no-print">
                <x-icon name="print" class="h-4 w-4" />
                {{ __('sitecheck.report.print') }}
            </button>
        </header>

        @yield('content')

        <footer class="mt-10 border-t border-line pt-4 text-[0.7rem] text-soft">
            {{ __('sitecheck.report.scanned_by') }} · {{ now()->translatedFormat('j F Y · H:i') }}
        </footer>
    </div>
</body>
</html>
