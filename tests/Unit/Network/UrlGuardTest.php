<?php

declare(strict_types=1);

namespace Tests\Unit\Network;

use App\Support\Network\UnsafeUrlException;
use App\Support\Network\UrlGuard;
use App\Support\Network\UrlNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeHostResolver;

final class UrlGuardTest extends TestCase
{
    private FakeHostResolver $resolver;

    private UrlGuard $guard;

    protected function setUp(): void
    {
        $this->resolver = new FakeHostResolver;
        $this->resolver->add('example.com', ['93.184.216.34'])
            ->add('public.example.com', ['93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946'])
            ->add('v6only.example.com', ['2606:2800:220:1:248:1893:25c8:1946'])
            ->add('mixed.example.com', ['93.184.216.34', '10.0.0.5'])
            ->add('rebind.example.com', ['93.184.216.34'])
            ->add('internal-target.example.com', ['127.0.0.1']);

        $this->guard = new UrlGuard($this->resolver);
    }

    #[DataProvider('blockedUrls')]
    public function test_blocked_targets(string $url, string $reason): void
    {
        try {
            $this->guard->validate($url);
            $this->fail('Expected UnsafeUrlException for '.$url);
        } catch (UnsafeUrlException $exception) {
            $this->assertSame($reason, $exception->reason, $url);
        }
    }

    /** @return array<string, array{string, string}> */
    public static function blockedUrls(): array
    {
        return [
            'loopback literal' => ['http://127.0.0.1/', 'blocked_address'],
            'private literal' => ['http://10.0.0.1/', 'blocked_address'],
            'metadata literal' => ['http://169.254.169.254/latest/meta-data/', 'blocked_address'],
            'cgnat literal' => ['http://100.64.0.1/', 'blocked_address'],
            'ipv6 loopback' => ['http://[::1]/', 'blocked_address'],
            'ipv6 mapped loopback' => ['http://[::ffff:127.0.0.1]/', 'blocked_address'],
            'ipv6 ula' => ['http://[fd00::1]/', 'blocked_address'],
            'host resolves private' => ['https://internal-target.example.com/', 'blocked_address'],
            'host mixed public and private' => ['https://mixed.example.com/', 'blocked_address'],
            'ftp scheme' => ['ftp://example.com/', 'invalid_scheme'],
            'file scheme' => ['file://example.com/etc/passwd', 'invalid_scheme'],
            'javascript scheme' => ['javascript:alert(1)', 'invalid_scheme'],
            'credentials' => ['https://user:secret@example.com/', 'credentials'],
            'username only' => ['https://user@example.com/', 'credentials'],
            'localhost' => ['http://localhost/', 'internal_hostname'],
            'dot local' => ['http://printer.local/', 'internal_hostname'],
            'dot internal' => ['http://metadata.google.internal/', 'internal_hostname'],
            'dot localdomain' => ['http://server.localdomain/', 'internal_hostname'],
            'home arpa' => ['http://router.home.arpa/', 'internal_hostname'],
            'reserved test tld' => ['http://example.test/', 'internal_hostname'],
            'single label' => ['http://intranet/', 'internal_hostname'],
            'obfuscated decimal' => ['http://2130706433/', 'obfuscated_host'],
            'obfuscated hex' => ['http://0x7f.0.0.1/', 'obfuscated_host'],
            'octal ip' => ['http://0177.0.0.1/', 'obfuscated_host'],
            'port 8080' => ['http://example.com:8080/', 'blocked_port'],
            'port 3306' => ['https://example.com:3306/', 'blocked_port'],
            'port 22' => ['https://example.com:22/', 'blocked_port'],
            'unresolvable' => ['https://does-not-exist.example.com/', 'unresolvable'],
            'backslash trick' => ['http://example.com\\@127.0.0.1/', 'invalid_characters'],
            'whitespace' => ['http://example.com /', 'invalid_characters'],
            'empty' => ['', 'empty'],
        ];
    }

    public function test_public_target_is_pinned_to_ipv4(): void
    {
        $target = $this->guard->validate('https://public.example.com/path?q=1');

        $this->assertSame('public.example.com', $target->host);
        $this->assertSame('https', $target->scheme);
        $this->assertSame(443, $target->port);
        $this->assertSame('93.184.216.34', $target->ip);
        $this->assertSame('https://public.example.com/path?q=1', $target->url);
        $this->assertContains('2606:2800:220:1:248:1893:25c8:1946', $target->resolvedIps);
    }

    public function test_ipv6_only_target_is_pinned_to_ipv6(): void
    {
        $target = $this->guard->validate('https://v6only.example.com/');

        $this->assertSame('2606:2800:220:1:248:1893:25c8:1946', $target->ip);
    }

    public function test_defaults_to_https_when_normalised_by_caller(): void
    {
        $url = (new UrlNormalizer)->normalize('example.com');
        $target = $this->guard->validate($url);

        $this->assertSame('https://example.com/', $target->url);
        $this->assertSame(443, $target->port);
    }

    public function test_ip_literals_do_not_use_the_resolver(): void
    {
        $this->resolver->resolved = [];
        $this->guard->validate('http://8.8.8.8/');

        $this->assertSame([], $this->resolver->resolved);
    }

    public function test_ipv6_host_is_normalised(): void
    {
        $target = $this->guard->validate('https://[2606:4700:4700::1111]/');

        $this->assertSame('2606:4700:4700::1111', $target->host);
        $this->assertSame('2606:4700:4700::1111', $target->ip);
    }

    public function test_idn_host_is_converted(): void
    {
        $resolver = new FakeHostResolver;
        $resolver->add('xn--mgbh0fb.com', ['93.184.216.34']);
        $guard = new UrlGuard($resolver);

        $target = $guard->validate('https://مثال.com/');

        $this->assertSame('xn--mgbh0fb.com', $target->host);
    }

    public function test_fragment_is_stripped(): void
    {
        $target = $this->guard->validate('https://example.com/page#section');

        $this->assertSame('https://example.com/page', $target->url);
    }

    public function test_explicit_default_port_is_normalised_away(): void
    {
        $target = $this->guard->validate('https://example.com:443/');

        $this->assertSame('https://example.com/', $target->url);
        $this->assertSame(443, $target->port);
    }
}
