<?php

declare(strict_types=1);

namespace App\Support\Network;

/**
 * Turns the free-text value typed by a visitor ("example.com",
 * "https://example.com/path?x=1") into a canonical absolute http(s) URL.
 * Semantic/network checks happen later in UrlGuard.
 */
final class UrlNormalizer
{
    private const MAX_LENGTH = 2048;

    public function normalize(string $raw): string
    {
        $raw = trim($raw);

        if ($raw === '') {
            throw UnsafeUrlException::because('empty');
        }

        if (strlen($raw) > self::MAX_LENGTH) {
            throw UnsafeUrlException::because('too_long');
        }

        if (preg_match('/[\x00-\x20\x7f]/', $raw) === 1 || str_contains($raw, '\\')) {
            throw UnsafeUrlException::because('invalid_characters');
        }

        if (preg_match('~^[a-z][a-z0-9+.\-]*://~i', $raw) !== 1) {
            $raw = 'https://'.$raw;
        }

        $parts = parse_url($raw);
        if ($parts === false || ! isset($parts['scheme'], $parts['host']) || $parts['host'] === '') {
            throw UnsafeUrlException::because('invalid_url');
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw UnsafeUrlException::because('invalid_scheme');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw UnsafeUrlException::because('credentials');
        }

        $host = $this->normalizeHost((string) $parts['host']);
        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
        $path = isset($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';

        $hostForUrl = str_contains($host, ':') ? '['.$host.']' : $host;
        $defaultPort = $scheme === 'https' ? 443 : 80;
        $portForUrl = $port === $defaultPort ? '' : ':'.$port;

        $url = $scheme.'://'.$hostForUrl.$portForUrl.$path;
        if (isset($parts['query']) && $parts['query'] !== '') {
            $url .= '?'.$parts['query'];
        }

        if (strlen($url) > self::MAX_LENGTH) {
            throw UnsafeUrlException::because('too_long');
        }

        return $url;
    }

    public function normalizeHost(string $host): string
    {
        $host = trim($host);
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        $host = strtolower(rtrim($host, '.'));

        if ($host === '') {
            throw UnsafeUrlException::because('invalid_host');
        }

        if (IpAddress::parse($host) !== null) {
            return $host;
        }

        if (preg_match('/[^\x20-\x7e]/', $host) === 1) {
            $converted = function_exists('idn_to_ascii')
                ? idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46)
                : false;

            if ($converted === false) {
                throw UnsafeUrlException::because('invalid_host');
            }

            $host = strtolower($converted);
        }

        if (strlen($host) > 253 || preg_match('/^[a-z0-9.\-]+$/', $host) !== 1) {
            throw UnsafeUrlException::because('invalid_host');
        }

        return $host;
    }
}
