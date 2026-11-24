<?php

declare(strict_types=1);

namespace App\Support\Network\Http;

use App\Support\Network\UnsafeUrlException;
use App\Support\Network\UrlGuard;
use App\Support\Network\ValidatedUrl;

/**
 * High-level HTTP fetcher: validates every hop, follows at most N redirects
 * manually, and never lets the transport re-resolve a hostname.
 */
final class SafeHttpFetcher
{
    private const REDIRECT_STATUSES = [301, 302, 303, 307, 308];

    public function __construct(
        private readonly UrlGuard $guard,
        private readonly HttpTransport $transport,
        private readonly RedirectResolver $redirects,
        private readonly string $userAgent,
        private readonly int $connectTimeout = 5,
        private readonly int $requestTimeout = 12,
        private readonly int $maxBodyBytes = 524288,
        private readonly int $maxHeaderBytes = 65536,
        private readonly int $maxRedirects = 5,
    ) {}

    public function fetch(string $url): HttpFetchResult
    {
        $current = $url;
        $hops = [];
        $visited = [];
        $startedAt = microtime(true);
        $deadline = $startedAt + ($this->requestTimeout * 2) + 10;

        while (true) {
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0.0) {
                throw new FetchFailedException('timeout', 'The redirect chain took too long.');
            }

            $target = $this->guard->validate($current);

            if (isset($visited[$target->url])) {
                throw new FetchFailedException('redirect_loop', 'The redirect chain loops.');
            }
            $visited[$target->url] = true;

            try {
                $response = $this->transport->send($this->buildRequest($target, $remaining));
            } catch (HttpTransportException $exception) {
                throw new FetchFailedException($exception->reason, $exception->getMessage());
            }

            $hops[] = new FetchHop(
                url: $target->url,
                status: $response->status,
                location: $response->location(),
                timeMs: $response->totalTimeMs,
                ip: $target->ip,
            );

            $location = $response->location();
            if (! in_array($response->status, self::REDIRECT_STATUSES, true) || $location === null || $location === '') {
                return new HttpFetchResult($hops, $target, $response, $target->url);
            }

            if (count($hops) - 1 >= $this->maxRedirects) {
                throw new FetchFailedException('too_many_redirects', 'The redirect limit was exceeded.');
            }

            try {
                $current = $this->redirects->resolve($target->url, $location);
            } catch (UnsafeUrlException $exception) {
                throw new FetchFailedException($exception->reason, $exception->getMessage());
            }
        }
    }

    private function buildRequest(ValidatedUrl $target, float $remaining): PinnedRequest
    {
        $timeout = max(1, (int) floor(min($this->requestTimeout, $remaining)));

        $fallbacks = array_values(array_filter(
            $target->resolvedIps,
            static fn (string $ip): bool => $ip !== $target->ip,
        ));

        return new PinnedRequest(
            url: $target->url,
            host: $target->host,
            port: $target->port,
            ip: $target->ip,
            timeoutSeconds: $timeout,
            connectTimeoutSeconds: min($this->connectTimeout, $timeout),
            maxBodyBytes: $this->maxBodyBytes,
            maxHeaderBytes: $this->maxHeaderBytes,
            userAgent: $this->userAgent,
            headers: [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.5',
                'Accept-Language: ar,en;q=0.8',
                'Connection: close',
            ],
            fallbackIps: array_slice($fallbacks, 0, 2),
        );
    }
}
