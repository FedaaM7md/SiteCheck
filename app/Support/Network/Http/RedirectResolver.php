<?php

declare(strict_types=1);

namespace App\Support\Network\Http;

use App\Support\Network\UnsafeUrlException;

/**
 * Resolves an HTTP Location header against the URL that produced it using the
 * RFC 3986 merge rules that matter here (absolute, scheme-relative, rooted,
 * query-only and relative references). Dot segments are collapsed.
 */
final class RedirectResolver
{
    public function resolve(string $baseUrl, string $location): string
    {
        $location = trim($location);

        if ($location === '') {
            throw UnsafeUrlException::because('invalid_redirect');
        }

        if (strlen($location) > 2048 || preg_match('/[\x00-\x20\x7f]/', $location) === 1 || str_contains($location, '\\')) {
            throw UnsafeUrlException::because('invalid_redirect');
        }

        // Fragments are never sent to the server. A fragment-only Location
        // refers to the current page.
        if (str_starts_with($location, '#')) {
            return $this->stripFragment($baseUrl);
        }

        if (str_contains($location, '#')) {
            $location = substr($location, 0, (int) strpos($location, '#'));
        }

        $base = parse_url($baseUrl);
        if ($base === false || ! isset($base['scheme'], $base['host'])) {
            throw UnsafeUrlException::because('invalid_redirect');
        }

        $scheme = strtolower((string) $base['scheme']);
        $defaultPort = $scheme === 'https' ? 443 : 80;
        $host = (string) $base['host'];
        $port = isset($base['port']) ? (int) $base['port'] : $defaultPort;
        $origin = $scheme.'://'.$host.($port === $defaultPort ? '' : ':'.$port);
        $basePath = isset($base['path']) && $base['path'] !== '' ? $base['path'] : '/';
        $baseQuery = isset($base['query']) && $base['query'] !== '' ? '?'.$base['query'] : '';

        if (str_starts_with($location, '//')) {
            return $this->stripFragment($scheme.':'.$location);
        }

        if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $location) === 1) {
            return $this->stripFragment($location);
        }

        if (str_starts_with($location, '?')) {
            return $this->stripFragment($origin.$basePath.$location);
        }

        if (str_starts_with($location, '#')) {
            return $this->stripFragment($origin.$basePath.$baseQuery);
        }

        if (str_starts_with($location, '/')) {
            return $this->stripFragment($origin.$this->normalizePath($location));
        }

        $directory = substr($basePath, 0, (int) strrpos($basePath, '/') + 1);
        if ($directory === '' || ! str_starts_with($directory, '/')) {
            $directory = '/';
        }

        return $this->stripFragment($origin.$this->normalizePath($directory.$location));
    }

    private function normalizePath(string $path): string
    {
        $segments = explode('/', $path);
        $output = [];

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '') {
                if ($segment === '' && $output === []) {
                    $output[] = '';
                }

                continue;
            }

            if ($segment === '..') {
                if (count($output) > 1) {
                    array_pop($output);
                }

                continue;
            }

            $output[] = $segment;
        }

        $normalized = implode('/', $output);
        if ($normalized === '' || $normalized === '.') {
            $normalized = '/';
        }

        if (! str_starts_with($normalized, '/')) {
            $normalized = '/'.$normalized;
        }

        // Preserve a meaningful trailing slash but never return a bare "//".
        if (str_ends_with($path, '/') && ! str_ends_with($normalized, '/')) {
            $normalized .= '/';
        }

        return $normalized === '//' ? '/' : $normalized;
    }

    private function stripFragment(string $url): string
    {
        $position = strpos($url, '#');

        return $position === false ? $url : substr($url, 0, $position);
    }
}
