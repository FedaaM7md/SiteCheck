<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\SiteCheck\Checks\DnsCheck;
use App\Domain\SiteCheck\Checks\EmailCheck;
use App\Domain\SiteCheck\Checks\HttpCheck;
use App\Domain\SiteCheck\Checks\SeoCheck;
use App\Domain\SiteCheck\Checks\SslCheck;
use App\Domain\SiteCheck\ScanService;
use App\Support\Network\Dns\DnsClient;
use App\Support\Network\HostResolver;
use App\Support\Network\Http\HttpTransport;
use App\Support\Network\Http\SafeHttpFetcher;
use App\Support\Network\Tls\TlsInspector;
use App\Support\Network\UrlGuard;
use Illuminate\Contracts\Foundation\Application;

/**
 * Binds a fully mocked network stack into the container. Nothing in the test
 * suite may ever perform a real DNS, TLS or HTTP request.
 */
final class NetworkFakes
{
    public function __construct(
        public readonly FakeHostResolver $resolver,
        public readonly FakeDnsClient $dns,
        public readonly FakeHttpTransport $http,
        public readonly FakeTlsInspector $tls,
    ) {}

    public static function make(): self
    {
        return new self(new FakeHostResolver, new FakeDnsClient, new FakeHttpTransport, new FakeTlsInspector);
    }

    public function bind(Application $app): self
    {
        $app->instance(HostResolver::class, $this->resolver);
        $app->instance(DnsClient::class, $this->dns);
        $app->instance(HttpTransport::class, $this->http);
        $app->instance(TlsInspector::class, $this->tls);

        foreach ([
            UrlGuard::class,
            SafeHttpFetcher::class,
            DnsCheck::class,
            SslCheck::class,
            EmailCheck::class,
            HttpCheck::class,
            SeoCheck::class,
            ScanService::class,
        ] as $abstract) {
            $app->forgetInstance($abstract);
        }

        return $this;
    }

    /**
     * Configure a healthy example.com target with every response the scanner
     * will ask for.
     */
    public function healthyExampleSite(): self
    {
        $this->resolver->add('example.com', ['93.184.216.34']);

        $this->dns
            ->set('example.com', 'A', FakeDnsClient::addresses('93.184.216.34'))
            ->set('example.com', 'AAAA', FakeDnsClient::addresses('2606:2800:220:1:248:1893:25c8:1946'))
            ->set('example.com', 'NS', [
                ['target' => 'a.iana-servers.net'],
                ['target' => 'b.iana-servers.net'],
            ])
            ->set('example.com', 'MX', [
                ['target' => 'mail.example.com', 'priority' => 10],
                ['target' => 'mail2.example.com', 'priority' => 20],
            ])
            ->set('example.com', 'TXT', FakeDnsClient::txt(
                'v=spf1 include:_spf.example.com ip4:93.184.216.34 -all',
                'google-site-verification=abc123',
            ))
            ->set('example.com', 'CAA', [
                ['flags' => 0, 'tag' => 'issue', 'value' => 'letsencrypt.org'],
            ])
            ->set('example.com', 'SOA', [
                ['mname' => 'ns.icann.org', 'rname' => 'noc.dns.icann.org', 'serial' => 2026010101],
            ])
            ->set('_dmarc.example.com', 'TXT', FakeDnsClient::txt(
                'v=DMARC1; p=reject; rua=mailto:dmarc@example.com; pct=100',
            ))
            ->set('google._domainkey.example.com', 'TXT', FakeDnsClient::txt(
                'v=DKIM1; k=rsa; p='.str_repeat('A', 400),
            ));

        $this->tls->report(FakeTlsInspector::validReport('example.com'));

        $html = <<<'HTML'
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Example Domain — a clear and descriptive title</title>
<meta name="description" content="This is a well sized meta description for the example domain, explaining what visitors can expect to find here.">
<link rel="canonical" href="https://example.com/">
<meta name="robots" content="index, follow">
<meta property="og:title" content="Example Domain">
<meta property="og:description" content="Example description for social sharing.">
<meta property="og:image" content="https://example.com/og.png">
<meta property="og:url" content="https://example.com/">
</head>
<body>
<h1>Example Domain</h1>
<p>Hello <img src="/logo.png" alt="Example logo"></p>
</body>
</html>
HTML;

        $this->http
            ->onResponse('https://example.com/', 200, [
                'content-type' => 'text/html; charset=utf-8',
                'strict-transport-security' => 'max-age=31536000; includeSubDomains',
                'content-security-policy' => "default-src 'self'; frame-ancestors 'none'",
                'x-content-type-options' => 'nosniff',
                'x-frame-options' => 'DENY',
                'referrer-policy' => 'strict-origin-when-cross-origin',
                'permissions-policy' => 'camera=(), microphone=(), geolocation=()',
                'server' => 'nginx',
                'set-cookie' => 'session=abc; Secure; HttpOnly; SameSite=Lax',
                'cache-control' => 'max-age=600',
            ], $html)
            ->onResponse('http://example.com/', 301, ['location' => 'https://example.com/'], '')
            ->onResponse('https://example.com/robots.txt', 200, ['content-type' => 'text/plain'], "User-agent: *\nAllow: /\nSitemap: https://example.com/sitemap.xml\n")
            ->onResponse('https://example.com/sitemap.xml', 200, ['content-type' => 'application/xml'], '<?xml version="1.0"?><urlset><url><loc>https://example.com/</loc></url></urlset>');

        return $this;
    }
}
