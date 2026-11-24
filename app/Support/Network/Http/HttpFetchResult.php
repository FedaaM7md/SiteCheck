<?php

declare(strict_types=1);

namespace App\Support\Network\Http;

use App\Support\Network\ValidatedUrl;

final class HttpFetchResult
{
    /**
     * @param  list<FetchHop>  $hops
     */
    public function __construct(
        public readonly array $hops,
        public readonly ValidatedUrl $finalTarget,
        public readonly RawHttpResponse $response,
        public readonly string $finalUrl,
    ) {}

    public function status(): int
    {
        return $this->response->status;
    }

    public function header(string $name): ?string
    {
        return $this->response->header($name);
    }

    /** @return list<string> */
    public function headerValues(string $name): array
    {
        return $this->response->headerValues($name);
    }

    public function body(): string
    {
        return $this->response->body;
    }

    public function redirectCount(): int
    {
        return count($this->hops) - 1;
    }

    /** @return list<array<string, mixed>> */
    public function hopsAsArray(): array
    {
        return array_map(static fn (FetchHop $hop): array => $hop->toArray(), $this->hops);
    }
}
