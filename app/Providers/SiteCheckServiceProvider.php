<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Providers;

use App\Domain\SiteCheck\Checks\DnsCheck;
use App\Domain\SiteCheck\Checks\EmailCheck;
use App\Domain\SiteCheck\Checks\HttpCheck;
use App\Domain\SiteCheck\Checks\SeoCheck;
use App\Domain\SiteCheck\Checks\SslCheck;
use App\Domain\SiteCheck\ScanService;
use App\Support\Network\Dns\CaaResolver;
use App\Support\Network\Dns\DnsClient;
use App\Support\Network\Dns\SystemDnsClient;
use App\Support\Network\HostResolver;
use App\Support\Network\Http\HttpTransport;
use App\Support\Network\Http\RedirectResolver;
use App\Support\Network\Http\SafeHttpFetcher;
use App\Support\Network\Http\StreamTransport;
use App\Support\Network\SystemHostResolver;
use App\Support\Network\Tls\StreamTlsInspector;
use App\Support\Network\Tls\TlsInspector;
use App\Support\Network\UrlGuard;
use App\Support\Network\UrlNormalizer;
use Illuminate\Support\ServiceProvider;

final class SiteCheckServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HostResolver::class, fn (): SystemHostResolver => new SystemHostResolver);
        $this->app->singleton(HttpTransport::class, fn (): StreamTransport => new StreamTransport);
        $this->app->singleton(TlsInspector::class, fn (): StreamTlsInspector => new StreamTlsInspector);

        $this->app->singleton(DnsClient::class, function ($app): SystemDnsClient {
            return new SystemDnsClient(
                (int) config('sitecheck.limits.dns_budget_seconds', 8),
                $app->make(CaaResolver::class),
            );
        });

        $this->app->singleton(UrlNormalizer::class);
        $this->app->singleton(UrlGuard::class, function ($app): UrlGuard {
            return new UrlGuard(
                $app->make(HostResolver::class),
                (array) config('sitecheck.allowed_ports', [80, 443]),
                (int) config('sitecheck.limits.max_url_length', 2048),
                $app->make(UrlNormalizer::class),
            );
        });

        $this->app->singleton(SafeHttpFetcher::class, function ($app): SafeHttpFetcher {
            return new SafeHttpFetcher(
                $app->make(UrlGuard::class),
                $app->make(HttpTransport::class),
                new RedirectResolver,
                (string) config('sitecheck.user_agent'),
                (int) config('sitecheck.limits.connect_timeout', 5),
                (int) config('sitecheck.limits.request_timeout', 12),
                (int) config('sitecheck.limits.max_body_bytes', 524288),
                (int) config('sitecheck.limits.max_header_bytes', 65536),
                (int) config('sitecheck.limits.max_redirects', 5),
            );
        });

        $this->app->singleton(DnsCheck::class, fn ($app): DnsCheck => new DnsCheck($app->make(DnsClient::class)));
        $this->app->singleton(SslCheck::class, fn ($app): SslCheck => new SslCheck(
            $app->make(TlsInspector::class),
            (int) config('sitecheck.limits.tls_timeout', 8),
        ));
        $this->app->singleton(EmailCheck::class, fn ($app): EmailCheck => new EmailCheck($app->make(DnsClient::class)));
        $this->app->singleton(HttpCheck::class, fn ($app): HttpCheck => new HttpCheck($app->make(SafeHttpFetcher::class)));
        $this->app->singleton(SeoCheck::class, fn ($app): SeoCheck => new SeoCheck($app->make(SafeHttpFetcher::class)));

        $this->app->singleton(ScanService::class);
    }
}
