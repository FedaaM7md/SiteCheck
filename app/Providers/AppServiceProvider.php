<?php

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('scan', function (Request $request): array {
            $limits = (array) config('sitecheck.rate_limits', []);

            return [
                Limit::perMinute((int) ($limits['per_ip_per_minute'] ?? 6))
                    ->by('scan-ip:'.$request->ip())
                    ->response(fn (Request $request, array $headers) => response()
                        ->view('errors.429', [], 429)
                        ->withHeaders($headers)),
                Limit::perMinute((int) ($limits['per_session_per_minute'] ?? 3))
                    ->by('scan-session:'.$request->session()->getId())
                    ->response(fn (Request $request, array $headers) => response()
                        ->view('errors.429', [], 429)
                        ->withHeaders($headers)),
                Limit::perDay((int) ($limits['per_ip_per_day'] ?? 120))
                    ->by('scan-ip-day:'.$request->ip()),
            ];
        });
    }
}
