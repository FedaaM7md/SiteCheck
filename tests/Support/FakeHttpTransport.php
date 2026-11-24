<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Network\Http\HttpTransport;
use App\Support\Network\Http\HttpTransportException;
use App\Support\Network\Http\PinnedRequest;
use App\Support\Network\Http\RawHttpResponse;
use Throwable;

final class FakeHttpTransport implements HttpTransport
{
    /** @var array<string, RawHttpResponse|Throwable> */
    private array $responses = [];

    /** @var list<PinnedRequest> */
    public array $requests = [];

    public function on(string $url, RawHttpResponse|Throwable $response): self
    {
        $this->responses[$url] = $response;

        return $this;
    }

    /**
     * @param  array<string, string|list<string>>  $headers
     */
    public function onResponse(string $url, int $status = 200, array $headers = [], string $body = ''): self
    {
        return $this->on($url, self::response($status, $headers, $body));
    }

    public function fail(string $url, string $reason = 'transport_error'): self
    {
        return $this->on($url, new HttpTransportException($reason, 'Fake failure'));
    }

    /**
     * @param  array<string, string|list<string>>  $headers
     */
    public static function response(int $status, array $headers = [], string $body = '', bool $truncated = false, int $timeMs = 40): RawHttpResponse
    {
        $normalised = [];
        foreach ($headers as $name => $value) {
            $normalised[strtolower($name)] = is_array($value) ? array_values($value) : [(string) $value];
        }

        return new RawHttpResponse(
            status: $status,
            headers: $normalised,
            body: $body,
            truncated: $truncated,
            totalTimeMs: $timeMs,
            connectTimeMs: 5,
            primaryIp: '93.184.216.34',
        );
    }

    public function send(PinnedRequest $request): RawHttpResponse
    {
        $this->requests[] = $request;

        $response = $this->responses[$request->url] ?? null;
        if ($response === null) {
            throw new HttpTransportException('no_response', 'No fake response configured for '.$request->url);
        }

        if ($response instanceof Throwable) {
            throw $response;
        }

        return $response;
    }

    /** @return list<string> */
    public function requestedUrls(): array
    {
        return array_map(static fn (PinnedRequest $request): string => $request->url, $this->requests);
    }
}
