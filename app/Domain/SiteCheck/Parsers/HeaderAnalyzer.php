<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Parsers;

final class HeaderAnalyzer
{
    /**
     * @param  array<string, list<string>>  $headers  lower-case name => values
     */
    public function analyze(array $headers): HeaderAnalysis
    {
        $hsts = $this->first($headers, 'strict-transport-security');
        $csp = $this->first($headers, 'content-security-policy');
        $xfo = $this->first($headers, 'x-frame-options');
        $referrer = $this->first($headers, 'referrer-policy');
        $permissions = $this->first($headers, 'permissions-policy');

        $hstsMaxAge = null;
        $hstsInclude = false;
        $hstsPreload = false;
        if ($hsts !== null && preg_match('/max-age\s*=\s*"?(\d+)"?/i', $hsts, $matches) === 1) {
            $hstsMaxAge = (int) $matches[1];
        }
        if ($hsts !== null) {
            $hstsInclude = stripos($hsts, 'includesubdomains') !== false;
            $hstsPreload = stripos($hsts, 'preload') !== false;
        }

        $frameAncestors = null;
        if ($csp !== null && preg_match('/(?:^|;)\s*frame-ancestors\s+([^;]+)/i', $csp, $matches) === 1) {
            $frameAncestors = trim($matches[1]);
        }

        return new HeaderAnalysis(
            hstsPresent: $hsts !== null,
            hstsMaxAge: $hstsMaxAge,
            hstsIncludeSubdomains: $hstsInclude,
            hstsPreload: $hstsPreload,
            cspPresent: $csp !== null,
            cspUnsafeInline: $csp !== null && stripos($csp, "'unsafe-inline'") !== false,
            cspUnsafeEval: $csp !== null && stripos($csp, "'unsafe-eval'") !== false,
            cspFrameAncestors: $frameAncestors !== null,
            xContentTypeOptions: strtolower((string) $this->first($headers, 'x-content-type-options')) === 'nosniff',
            xFrameOptions: $xfo,
            frameAncestors: $frameAncestors,
            referrerPolicy: $referrer,
            permissionsPolicy: $permissions !== null,
            cookies: $this->parseCookies($headers['set-cookie'] ?? []),
            server: $this->first($headers, 'server'),
            poweredBy: $this->first($headers, 'x-powered-by'),
            generator: $this->first($headers, 'x-generator'),
            aspNetVersion: $this->first($headers, 'x-aspnet-version'),
            xRobotsTag: $this->first($headers, 'x-robots-tag'),
            contentType: $this->first($headers, 'content-type'),
            contentEncoding: $this->first($headers, 'content-encoding'),
            contentLength: $this->first($headers, 'content-length'),
            cacheControl: $this->first($headers, 'cache-control'),
            cspValue: $csp,
            hstsValue: $hsts,
        );
    }

    /** @param array<string, list<string>> $headers */
    private function first(array $headers, string $name): ?string
    {
        $values = $headers[$name] ?? [];

        return $values === [] ? null : (string) $values[0];
    }

    /**
     * @param  list<string>  $setCookies
     * @return list<array{name: string, secure: bool, http_only: bool, same_site: ?string}>
     */
    private function parseCookies(array $setCookies): array
    {
        $cookies = [];

        foreach ($setCookies as $raw) {
            $parts = explode(';', $raw);
            $pair = trim((string) array_shift($parts));
            if ($pair === '' || ! str_contains($pair, '=')) {
                continue;
            }

            [$name] = explode('=', $pair, 2);
            $name = trim($name);

            $secure = false;
            $httpOnly = false;
            $sameSite = null;

            foreach ($parts as $attribute) {
                $attribute = trim($attribute);
                $lower = strtolower($attribute);

                if ($lower === 'secure') {
                    $secure = true;
                } elseif ($lower === 'httponly') {
                    $httpOnly = true;
                } elseif (str_starts_with($lower, 'samesite=')) {
                    $sameSite = strtolower(trim(substr($attribute, 9)));
                }
            }

            if ($name !== '') {
                $cookies[] = [
                    'name' => $name,
                    'secure' => $secure,
                    'http_only' => $httpOnly,
                    'same_site' => $sameSite,
                ];
            }
        }

        return $cookies;
    }
}
