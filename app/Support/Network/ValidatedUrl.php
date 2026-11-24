<?php

declare(strict_types=1);

namespace App\Support\Network;

final class ValidatedUrl
{
    /**
     * @param  list<string>  $resolvedIps
     */
    public function __construct(
        public readonly string $scheme,
        public readonly string $host,
        public readonly int $port,
        public readonly string $ip,
        public readonly string $path,
        public readonly string $url,
        public readonly array $resolvedIps,
    ) {}

    public function isHttps(): bool
    {
        return $this->scheme === 'https';
    }

    public function origin(): string
    {
        return $this->scheme.'://'.$this->hostWithOptionalPort();
    }

    /**
     * Build an absolute URL on the same origin (used for robots.txt and
     * sitemap.xml checks).
     */
    public function withPath(string $path): string
    {
        if ($path === '' || $path[0] !== '/') {
            $path = '/'.$path;
        }

        return $this->origin().$path;
    }

    public function hostHeader(): string
    {
        return $this->hostWithOptionalPort();
    }

    private function hostWithOptionalPort(): string
    {
        $default = $this->scheme === 'https' ? 443 : 80;
        $host = str_contains($this->host, ':') ? '['.$this->host.']' : $this->host;

        return $this->port === $default ? $host : $host.':'.$this->port;
    }
}
