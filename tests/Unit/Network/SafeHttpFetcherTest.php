<?php

declare(strict_types=1);

namespace Tests\Unit\Network;

use App\Support\Network\Http\FetchFailedException;
use App\Support\Network\Http\RedirectResolver;
use App\Support\Network\Http\SafeHttpFetcher;
use App\Support\Network\UnsafeUrlException;
use App\Support\Network\UrlGuard;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeHostResolver;
use Tests\Support\FakeHttpTransport;

final class SafeHttpFetcherTest extends TestCase
{
    private FakeHostResolver $resolver;

    private FakeHttpTransport $transport;

    private SafeHttpFetcher $fetcher;

    protected function setUp(): void
    {
        $this->resolver = new FakeHostResolver;
        $this->resolver->add('example.com', ['93.184.216.34'])
            ->add('cdn.example.com', ['93.184.216.35']);

        $this->transport = new FakeHttpTransport;

        $this->fetcher = new SafeHttpFetcher(
            new UrlGuard($this->resolver),
            $this->transport,
            new RedirectResolver,
            'Fedaa-Test/1.0',
            2,
            4,
            1024,
            8192,
            5,
        );
    }

    public function test_follows_relative_redirects_and_returns_the_final_response(): void
    {
        $this->transport
            ->onResponse('https://example.com/', 301, ['location' => '/home'])
            ->onResponse('https://example.com/home', 302, ['location' => 'https://example.com/home?utm=1'])
            ->onResponse('https://example.com/home?utm=1', 200, ['content-type' => 'text/html'], '<html>ok</html>');

        $result = $this->fetcher->fetch('https://example.com/');

        $this->assertSame(200, $result->status());
        $this->assertSame('https://example.com/home?utm=1', $result->finalUrl);
        $this->assertCount(3, $result->hops);
        $this->assertSame(2, $result->redirectCount());
        $this->assertSame('<html>ok</html>', $result->body());
    }

    public function test_rejects_a_redirect_to_a_private_address(): void
    {
        $this->transport->onResponse('https://example.com/', 302, ['location' => 'http://127.0.0.1/admin']);

        try {
            $this->fetcher->fetch('https://example.com/');
            $this->fail('Expected the redirect to be blocked.');
        } catch (UnsafeUrlException $exception) {
            $this->assertSame('blocked_address', $exception->reason);
        }

        $this->assertCount(1, $this->transport->requests);
    }

    public function test_rejects_a_redirect_to_a_reserved_hostname(): void
    {
        $this->transport->onResponse('https://example.com/', 302, ['location' => 'http://metadata.google.internal/']);

        try {
            $this->fetcher->fetch('https://example.com/');
            $this->fail('Expected the redirect to be blocked.');
        } catch (UnsafeUrlException $exception) {
            $this->assertSame('internal_hostname', $exception->reason);
        }

        $this->assertCount(1, $this->transport->requests);
    }

    public function test_dns_rebinding_is_blocked_on_the_second_resolution(): void
    {
        $calls = 0;
        $resolver = new FakeHostResolver;
        $resolver->add('rebind.example.com', function () use (&$calls): array {
            $calls++;

            return $calls === 1 ? ['93.184.216.34'] : ['127.0.0.1'];
        });

        $transport = new FakeHttpTransport;
        $transport->onResponse('https://rebind.example.com/', 302, ['location' => '/login']);

        $fetcher = new SafeHttpFetcher(new UrlGuard($resolver), $transport, new RedirectResolver, 'test');

        try {
            $fetcher->fetch('https://rebind.example.com/');
            $this->fail('Rebinding must be rejected.');
        } catch (UnsafeUrlException $exception) {
            $this->assertSame('blocked_address', $exception->reason);
        }

        $this->assertCount(1, $transport->requests);
        $this->assertSame(2, $calls);
    }

    public function test_redirect_limit_is_enforced(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $from = $i === 0 ? 'https://example.com/' : 'https://example.com/r'.$i;
            $to = '/r'.($i + 1);
            $this->transport->onResponse($from, 302, ['location' => $to]);
        }

        $this->expectException(FetchFailedException::class);

        try {
            $this->fetcher->fetch('https://example.com/');
        } catch (FetchFailedException $exception) {
            $this->assertSame('too_many_redirects', $exception->reason);

            throw $exception;
        }
    }

    public function test_redirect_loops_are_rejected(): void
    {
        $this->transport
            ->onResponse('https://example.com/', 302, ['location' => '/a'])
            ->onResponse('https://example.com/a', 302, ['location' => 'https://example.com/']);

        try {
            $this->fetcher->fetch('https://example.com/');
            $this->fail('Loop must be rejected.');
        } catch (FetchFailedException $exception) {
            $this->assertSame('redirect_loop', $exception->reason);
        }
    }

    public function test_transport_errors_are_wrapped(): void
    {
        $this->transport->fail('https://example.com/');

        try {
            $this->fetcher->fetch('https://example.com/');
            $this->fail('Expected a failure.');
        } catch (FetchFailedException $exception) {
            $this->assertSame('transport_error', $exception->reason);
        }
    }

    public function test_each_hop_is_pinned_to_the_validated_ip(): void
    {
        $this->transport
            ->onResponse('https://example.com/', 302, ['location' => 'https://cdn.example.com/asset'])
            ->onResponse('https://cdn.example.com/asset', 200, [], 'asset');

        $result = $this->fetcher->fetch('https://example.com/');

        $this->assertCount(2, $this->transport->requests);
        $this->assertSame('93.184.216.34', $this->transport->requests[0]->ip);
        $this->assertSame('93.184.216.35', $this->transport->requests[1]->ip);
        $this->assertSame('cdn.example.com', $result->finalTarget->host);
    }

    public function test_redirect_to_a_disallowed_scheme_is_rejected(): void
    {
        $this->transport->onResponse('https://example.com/', 302, ['location' => 'ftp://example.com/file']);

        try {
            $this->fetcher->fetch('https://example.com/');
            $this->fail('Expected rejection.');
        } catch (UnsafeUrlException $exception) {
            $this->assertSame('invalid_scheme', $exception->reason);
        }
    }

    public function test_location_with_credentials_is_rejected_at_the_next_guard(): void
    {
        $this->transport->onResponse('https://example.com/', 302, ['location' => 'https://user:pass@example.com/']);

        try {
            $this->fetcher->fetch('https://example.com/');
            $this->fail('Expected rejection.');
        } catch (UnsafeUrlException $exception) {
            $this->assertSame('credentials', $exception->reason);
        }
    }
}
