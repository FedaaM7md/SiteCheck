<?php

declare(strict_types=1);

namespace App\Support\Network;

/**
 * The SSRF gate. Every outbound request (including each redirect hop and
 * every robots.txt/sitemap.xml probe) must pass through validate() first.
 *
 * The guard resolves the hostname itself, rejects the whole host if any
 * resolved address is not publicly routable, and returns the exact IP that
 * the transport must pin the connection to. Pinning is what defeats DNS
 * rebinding between validation and connection time.
 */
final class UrlGuard
{
    /** @var list<string> */
    private const BLOCKED_HOST_SUFFIXES = [
        'localhost',
        'local',
        'localdomain',
        'internal',
        'intranet',
        'corp',
        'lan',
        'home.arpa',
        'home',
        'test',
        'invalid',
        'example',
        'onion',
        'arpa',
    ];

    /** @param list<int> $allowedPorts */
    public function __construct(
        private readonly HostResolver $resolver,
        private readonly array $allowedPorts = [80, 443],
        private readonly int $maxUrlLength = 2048,
        private readonly UrlNormalizer $normalizer = new UrlNormalizer,
    ) {}

    public function validate(string $rawUrl): ValidatedUrl
    {
        $rawUrl = trim($rawUrl);

        if ($rawUrl === '') {
            throw UnsafeUrlException::because('empty');
        }

        if (strlen($rawUrl) > $this->maxUrlLength) {
            throw UnsafeUrlException::because('too_long');
        }

        if (preg_match('/[\x00-\x20\x7f]/', $rawUrl) === 1 || str_contains($rawUrl, '\\')) {
            throw UnsafeUrlException::because('invalid_characters');
        }

        $parts = parse_url($rawUrl);
        if ($parts === false) {
            throw UnsafeUrlException::because('invalid_url');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme === '') {
            throw UnsafeUrlException::because('invalid_url');
        }

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw UnsafeUrlException::because('invalid_scheme');
        }

        if (! isset($parts['host']) || $parts['host'] === '') {
            throw UnsafeUrlException::because('invalid_url');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw UnsafeUrlException::because('credentials');
        }

        $host = $this->normalizer->normalizeHost((string) $parts['host']);
        $this->assertHostIsAllowed($host);

        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
        if (! in_array($port, $this->allowedPorts, true)) {
            throw UnsafeUrlException::because('blocked_port', 'Only ports 80 and 443 are allowed.');
        }

        $literal = IpAddress::parse($host);
        $resolved = $literal !== null ? [$literal] : $this->resolveHost($host);

        foreach ($resolved as $address) {
            if (! $address->isPublic()) {
                throw UnsafeUrlException::because(
                    'blocked_address',
                    sprintf('%s resolves to a non-public address (%s).', $host, $address->blockedReason() ?? 'blocked'),
                );
            }
        }

        /** @var list<IpAddress> $resolved */
        $pinned = $this->pickPinnedAddress($resolved);

        $path = isset($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';
        $defaultPort = $scheme === 'https' ? 443 : 80;
        $hostForUrl = str_contains($host, ':') ? '['.$host.']' : $host;
        $portForUrl = $port === $defaultPort ? '' : ':'.$port;

        $url = $scheme.'://'.$hostForUrl.$portForUrl.$path.$query;

        return new ValidatedUrl(
            scheme: $scheme,
            host: $host,
            port: $port,
            ip: $pinned->normalized(),
            path: $path.$query,
            url: $url,
            resolvedIps: array_map(static fn (IpAddress $ip): string => $ip->normalized(), $resolved),
        );
    }

    private function assertHostIsAllowed(string $host): void
    {
        if (IpAddress::parse($host) !== null) {
            return;
        }

        // Reject decimal/hex/octal IP encodings such as 2130706433 or 0x7f.0.0.1.
        if (preg_match('/^(0x[0-9a-f]+|[0-9]+)(\.(0x[0-9a-f]+|[0-9]+))*$/i', $host) === 1) {
            throw UnsafeUrlException::because('obfuscated_host');
        }

        if (! str_contains($host, '.')) {
            throw UnsafeUrlException::because('internal_hostname', 'Single-label hostnames are never scanned.');
        }

        foreach (self::BLOCKED_HOST_SUFFIXES as $suffix) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                throw UnsafeUrlException::because('internal_hostname', 'Reserved or internal hostname suffix.');
            }
        }

        // Defense in depth: reject remaining non-printable or odd host shapes.
        if (preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)+$/', $host) !== 1) {
            throw UnsafeUrlException::because('invalid_host');
        }
    }

    /** @return list<IpAddress> */
    private function resolveHost(string $host): array
    {
        try {
            $raw = $this->resolver->resolve($host);
        } catch (HostResolutionException $exception) {
            throw UnsafeUrlException::because('unresolvable', $exception->getMessage());
        }

        $addresses = [];
        foreach ($raw as $ip) {
            $address = IpAddress::parse($ip);
            if ($address === null) {
                throw UnsafeUrlException::because('unresolvable', 'Resolver returned a malformed address.');
            }
            $addresses[$address->normalized()] = $address;
        }

        if ($addresses === []) {
            throw UnsafeUrlException::because('unresolvable');
        }

        return array_values($addresses);
    }

    /** @param list<IpAddress> $addresses */
    private function pickPinnedAddress(array $addresses): IpAddress
    {
        foreach ($addresses as $address) {
            if ($address->isIpv4()) {
                return $address;
            }
        }

        return $addresses[0];
    }
}
