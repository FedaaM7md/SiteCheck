@php
    $title = trim($__env->yieldContent('title'));
    $metaDescription = trim($__env->yieldContent('meta_description', __('sitecheck.meta.description')));
    $canonical = url()->current();
    $ogImage = asset('og-image.png');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#1F4FD8">
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonical }}">

    <title>{{ $title !== '' ? $title.' — ' : '' }}{{ __('sitecheck.app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ __('sitecheck.app.name') }}">
    <meta property="og:title" content="{{ $title !== '' ? $title.' — ' : '' }}{{ __('sitecheck.app.name') }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_EG' : 'en_US' }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title !== '' ? $title.' — ' : '' }}{{ __('sitecheck.app.name') }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col font-sans antialiased">
    <a href="#main" class="skip-link">{{ __('sitecheck.nav.skip') }}</a>

    <header class="sticky top-0 z-40 border-b border-line bg-white no-print">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="brand-link flex items-center gap-2.5" aria-label="{{ __('sitecheck.app.name') }}">
                <x-logo class="h-9 w-9" />
                <span class="text-base font-bold tracking-tight text-ink">{{ __('sitecheck.app.name') }}</span>
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="{{ __('sitecheck.nav.menu') }}">
                <a href="{{ route('home') }}" class="btn-ghost {{ request()->routeIs('home') ? 'bg-report text-ink' : '' }}" @if (request()->routeIs('home')) aria-current="page" @endif>
                    {{ __('sitecheck.nav.home') }}
                </a>
                <a href="{{ route('history.index') }}" class="btn-ghost {{ request()->routeIs('history.*') ? 'bg-report text-ink' : '' }}" @if (request()->routeIs('history.*')) aria-current="page" @endif>
                    {{ __('sitecheck.nav.history') }}
                </a>
                <a href="{{ route('pages.about') }}" class="btn-ghost {{ request()->routeIs('pages.about') ? 'bg-report text-ink' : '' }}" @if (request()->routeIs('pages.about')) aria-current="page" @endif>
                    {{ __('sitecheck.nav.about') }}
                </a>
                <a href="{{ route('pages.help') }}" class="btn-ghost {{ request()->routeIs('pages.help') ? 'bg-report text-ink' : '' }}" @if (request()->routeIs('pages.help')) aria-current="page" @endif>
                    {{ __('sitecheck.nav.help') }}
                </a>
            </nav>

            <div class="flex items-center gap-2">
                <x-language-switch class="hidden md:block" />

                <a href="{{ route('home') }}#scan" class="btn-primary btn-sm hidden sm:inline-flex">
                    <x-icon name="search" class="h-4 w-4" />
                    {{ __('sitecheck.nav.new_scan') }}
                </a>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded border border-line-strong text-soft hover:bg-report md:hidden"
                    data-menu-toggle
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    aria-label="{{ __('sitecheck.nav.menu') }}"
                >
                    <span data-menu-icon="closed"><x-icon name="menu" class="h-5 w-5" /></span>
                    <span data-menu-icon="open" hidden><x-icon name="close" class="h-5 w-5" /></span>
                </button>
            </div>
        </div>

        <div id="mobile-menu" data-menu hidden class="border-t border-line bg-white md:hidden">
            <div class="mx-auto flex max-w-6xl flex-col gap-1.5 px-4 py-4 sm:px-6">
                <a href="{{ route('home') }}" class="btn-ghost justify-start">{{ __('sitecheck.nav.home') }}</a>
                <a href="{{ route('history.index') }}" class="btn-ghost justify-start">{{ __('sitecheck.nav.history') }}</a>
                <a href="{{ route('pages.about') }}" class="btn-ghost justify-start">{{ __('sitecheck.nav.about') }}</a>
                <a href="{{ route('pages.help') }}" class="btn-ghost justify-start">{{ __('sitecheck.nav.help') }}</a>
                <a href="{{ route('home') }}#scan" class="btn-primary justify-start">{{ __('sitecheck.nav.new_scan') }}</a>
                <x-language-switch class="pt-2" />
            </div>
        </div>
    </header>

    @if (session('status'))
        <div class="mx-auto w-full max-w-6xl px-4 pt-4 sm:px-6 lg:px-8 no-print">
            <div class="alert-success" role="status">
                <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0" />
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-line bg-report no-print">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                <div class="flex items-start gap-3">
                    <x-logo class="h-8 w-8" />
                    <div>
                        <p class="text-sm font-bold text-ink">{{ __('sitecheck.app.name') }}</p>
                        <p class="mt-0.5 max-w-md text-xs leading-relaxed text-soft">{{ __('sitecheck.footer.note') }}</p>
                    </div>
                </div>

                <nav class="flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold text-soft" aria-label="{{ __('sitecheck.nav.menu') }}">
                    <a href="{{ route('home') }}" class="hover:text-brand-700 hover:underline">{{ __('sitecheck.nav.home') }}</a>
                    <a href="{{ route('home') }}#scan" class="hover:text-brand-700 hover:underline">{{ __('sitecheck.nav.new_scan') }}</a>
                    <a href="{{ route('history.index') }}" class="hover:text-brand-700 hover:underline">{{ __('sitecheck.nav.history') }}</a>
                    <a href="{{ route('pages.about') }}" class="hover:text-brand-700 hover:underline">{{ __('sitecheck.nav.about') }}</a>
                    <a href="{{ route('pages.help') }}" class="hover:text-brand-700 hover:underline">{{ __('sitecheck.nav.help') }}</a>
                    <a href="{{ route('pages.privacy') }}" class="hover:text-brand-700 hover:underline">{{ __('sitecheck.nav.privacy') }}</a>
                    <a href="{{ route('pages.terms') }}" class="hover:text-brand-700 hover:underline">{{ __('sitecheck.nav.terms') }}</a>
                    <a href="{{ route('pages.changelog') }}" class="hover:text-brand-700 hover:underline">{{ __('sitecheck.nav.changelog') }}</a>
                </nav>
            </div>

            <div class="mt-6 flex flex-col gap-2 border-t border-line pt-4 text-xs text-soft sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col gap-1">
                    <p>© 2026 {{ __('sitecheck.app.name') }}. {{ __('sitecheck.footer.rights') }}</p>
                    <p>
                        <a href="https://fedaamohamed.com" rel="noopener" class="tap-link text-faint hover:text-brand-700 hover:underline">{{ __('sitecheck.footer.credit') }}</a>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('pages.changelog') }}" class="tap-link text-[0.7rem] text-faint hover:text-brand-700 hover:underline">{{ __('sitecheck.footer.version') }}</a>
                    @if (config('sitecheck.demo_mode'))
                        <span class="text-[0.7rem] text-faint">{{ __('sitecheck.footer.demo_note') }}</span>
                    @endif
                    <x-language-switch />
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
