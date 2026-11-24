<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
        ]);

        $request->session()->put('locale', $data['locale']);

        return redirect()
            ->to($this->safeBackUrl($request))
            ->with('status', __('sitecheck.flash.locale_changed'));
    }

    private function safeBackUrl(Request $request): string
    {
        $referer = (string) $request->headers->get('referer', '');
        $requestHost = $request->getHost();

        if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $requestHost) {
            return $referer;
        }

        return route('home');
    }
}
