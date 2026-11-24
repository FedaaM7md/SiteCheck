<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Checks;

use App\Domain\SiteCheck\Dto\Finding;
use App\Domain\SiteCheck\Dto\SectionResult;
use App\Domain\SiteCheck\Parsers\HeaderAnalyzer;
use App\Support\Network\Http\FetchFailedException;
use App\Support\Network\Http\HttpFetchResult;
use App\Support\Network\Http\SafeHttpFetcher;
use App\Support\Network\UnsafeUrlException;
use App\Support\Network\ValidatedUrl;
use Throwable;

final class HttpCheck
{
    private const REFERRER_PASS = [
        'no-referrer',
        'strict-origin',
        'strict-origin-when-cross-origin',
        'same-origin',
    ];

    public function __construct(
        private readonly SafeHttpFetcher $fetcher,
        private readonly HeaderAnalyzer $analyzer = new HeaderAnalyzer,
    ) {}

    public function run(ValidatedUrl $target): HttpAuditResult
    {
        try {
            $primary = $this->fetcher->fetch($target->url);
        } catch (FetchFailedException|UnsafeUrlException $exception) {
            return new HttpAuditResult(
                new SectionResult('http', [
                    Finding::fail('http_reachable', 'http', 6, ['reason' => $exception->reason]),
                ], [], 'http_unreachable'),
                null,
                null,
            );
        }

        $response = $primary->response;
        $analysis = $this->analyzer->analyze($response->headers);
        $findings = [];

        // Status --------------------------------------------------------------
        if ($response->status >= 200 && $response->status < 300) {
            $findings[] = Finding::pass('http_status', 'http', 2, ['status' => $response->status]);
        } elseif ($response->status >= 300 && $response->status < 400) {
            $findings[] = Finding::warn('http_status', 'http', 2, ['status' => $response->status]);
        } else {
            $findings[] = Finding::fail('http_status', 'http', 2, ['status' => $response->status]);
        }

        // HTTPS ---------------------------------------------------------------
        $secureFinal = $primary->finalTarget->isHttps();
        if ($secureFinal) {
            $findings[] = Finding::pass('http_https', 'http', 3);
        } else {
            $findings[] = Finding::fail('http_https', 'http', 3);
        }

        // Upgrade behaviour ---------------------------------------------------
        $plain = $this->checkUpgrade($target, $primary);
        $findings[] = $plain['finding'];

        // HSTS ----------------------------------------------------------------
        if (! $analysis->hstsPresent) {
            $findings[] = Finding::fail('http_hsts', 'http', 3);
        } elseif ($analysis->hstsMaxAge === null || $analysis->hstsMaxAge < 15_552_000) {
            $findings[] = Finding::warn('http_hsts', 'http', 3, ['days' => (int) floor(((int) $analysis->hstsMaxAge) / 86400)]);
        } else {
            $findings[] = Finding::pass('http_hsts', 'http', 3, [
                'days' => (int) floor($analysis->hstsMaxAge / 86400),
            ]);
        }

        // CSP -----------------------------------------------------------------
        if (! $analysis->cspPresent) {
            $findings[] = Finding::fail('http_csp', 'http', 3);
        } elseif ($analysis->cspUnsafeInline || $analysis->cspUnsafeEval) {
            $findings[] = Finding::warn('http_csp', 'http', 3, [
                'unsafe_inline' => $analysis->cspUnsafeInline ? 1 : 0,
                'unsafe_eval' => $analysis->cspUnsafeEval ? 1 : 0,
            ]);
        } else {
            $findings[] = Finding::pass('http_csp', 'http', 3);
        }

        // X-Content-Type-Options ----------------------------------------------
        $findings[] = $analysis->xContentTypeOptions
            ? Finding::pass('http_xcto', 'http', 2)
            : Finding::fail('http_xcto', 'http', 2);

        // Framing -------------------------------------------------------------
        $xfo = strtoupper((string) $analysis->xFrameOptions);
        $framingProtected = in_array($xfo, ['DENY', 'SAMEORIGIN'], true) || $analysis->cspFrameAncestors;
        if ($framingProtected) {
            $findings[] = Finding::pass('http_frame', 'http', 2);
        } elseif ($analysis->xFrameOptions !== null) {
            $findings[] = Finding::warn('http_frame', 'http', 2);
        } else {
            $findings[] = Finding::fail('http_frame', 'http', 2);
        }

        // Referrer-Policy -----------------------------------------------------
        $referrer = strtolower((string) $analysis->referrerPolicy);
        if (in_array($referrer, self::REFERRER_PASS, true)) {
            $findings[] = Finding::pass('http_referrer', 'http', 1);
        } elseif ($referrer === '') {
            $findings[] = Finding::fail('http_referrer', 'http', 1);
        } else {
            $findings[] = Finding::warn('http_referrer', 'http', 1, ['policy' => $referrer]);
        }

        // Permissions-Policy --------------------------------------------------
        $findings[] = $analysis->permissionsPolicy
            ? Finding::pass('http_permissions', 'http', 1)
            : Finding::warn('http_permissions', 'http', 1);

        // Cookies -------------------------------------------------------------
        $cookies = $analysis->cookies;
        if ($cookies === []) {
            $findings[] = Finding::info('http_cookies', 'http', ['count' => 0]);
        } else {
            $insecure = 0;
            $noHttpOnly = 0;
            $noSameSite = 0;
            foreach ($cookies as $cookie) {
                if (! $cookie['secure']) {
                    $insecure++;
                }
                if (! $cookie['http_only']) {
                    $noHttpOnly++;
                }
                if ($cookie['same_site'] === null) {
                    $noSameSite++;
                }
            }

            $params = [
                'count' => count($cookies),
                'insecure' => $insecure,
                'no_httponly' => $noHttpOnly,
                'no_samesite' => $noSameSite,
            ];

            if ($insecure === 0 && $noHttpOnly === 0 && $noSameSite === 0) {
                $findings[] = Finding::pass('http_cookies', 'http', 2, $params);
            } elseif ($insecure > 0) {
                $findings[] = Finding::fail('http_cookies', 'http', 2, $params);
            } else {
                $findings[] = Finding::warn('http_cookies', 'http', 2, $params);
            }
        }

        // Server header leakage ------------------------------------------------
        $leaksVersion = $analysis->server !== null && preg_match('/\d+\.\d+/', $analysis->server) === 1;
        $hasPoweredBy = $analysis->poweredBy !== null || $analysis->aspNetVersion !== null || $analysis->generator !== null;
        if ($leaksVersion || $hasPoweredBy) {
            $findings[] = Finding::warn('http_server_header', 'http', 1, [
                'server' => $this->short(implode('; ', array_filter([
                    $analysis->server,
                    $analysis->poweredBy,
                    $analysis->aspNetVersion,
                    $analysis->generator,
                ]))),
            ]);
        } else {
            $findings[] = Finding::pass('http_server_header', 'http', 1);
        }

        // Response time --------------------------------------------------------
        $timeMs = $response->totalTimeMs;
        if ($timeMs <= 1000) {
            $findings[] = Finding::pass('http_response_time', 'http', 1, ['ms' => $timeMs]);
        } elseif ($timeMs <= 2500) {
            $findings[] = Finding::warn('http_response_time', 'http', 1, ['ms' => $timeMs]);
        } else {
            $findings[] = Finding::fail('http_response_time', 'http', 1, ['ms' => $timeMs]);
        }

        // Informational --------------------------------------------------------
        $findings[] = Finding::info('http_compression', 'http', [
            'encoding' => $analysis->contentEncoding ?? 'none',
        ]);

        if ($primary->redirectCount() > 0) {
            $findings[] = Finding::info('http_redirects', 'http', [
                'count' => $primary->redirectCount(),
            ], ['hops' => $primary->hopsAsArray()]);
        }

        $details = [
            'final_url' => $primary->finalUrl,
            'status' => $response->status,
            'total_time_ms' => $response->totalTimeMs,
            'connect_time_ms' => $response->connectTimeMs,
            'body_truncated' => $response->truncated,
            'redirects' => $primary->hopsAsArray(),
            'headers' => $this->relevantHeaders($response->headers),
            'cookies' => $cookies,
            'plain_http' => $plain['details'],
            'server' => $analysis->server,
            'powered_by' => $analysis->poweredBy,
        ];

        return new HttpAuditResult(new SectionResult('http', $findings, $details), $primary, $plain['result']);
    }

    /**
     * @return array{finding: Finding, result: ?HttpFetchResult, details: array<string, mixed>}
     */
    private function checkUpgrade(ValidatedUrl $target, HttpFetchResult $primary): array
    {
        $plainUrl = $target->isHttps()
            ? 'http://'.$this->hostPort($target->host, 80).'/'
            : $target->url;

        $httpsUrl = $target->isHttps()
            ? $target->url
            : 'https://'.$this->hostPort($target->host, 443).'/';

        try {
            $probe = $this->fetcher->fetch($target->isHttps() ? $plainUrl : $httpsUrl);
        } catch (Throwable $exception) {
            $details = [
                'probe' => $target->isHttps() ? 'http' : 'https',
                'available' => false,
                'blocked' => $exception instanceof UnsafeUrlException,
            ];

            if ($target->isHttps()) {
                // No plain HTTP service is the desired state, but a blocked
                // redirect is a real finding rather than a pass.
                return [
                    'finding' => $exception instanceof UnsafeUrlException
                        ? Finding::warn('http_upgrade', 'http', 3)
                        : Finding::pass('http_upgrade', 'http', 3),
                    'result' => null,
                    'details' => $details,
                ];
            }

            return [
                'finding' => Finding::fail('http_upgrade', 'http', 3),
                'result' => null,
                'details' => $details,
            ];
        }

        $details = [
            'probe' => $target->isHttps() ? 'http' : 'https',
            'available' => true,
            'status' => $probe->status(),
            'final_url' => $probe->finalUrl,
            'redirects' => $probe->hopsAsArray(),
        ];

        if ($target->isHttps()) {
            $redirectsToHttps = $probe->finalTarget->isHttps();

            return [
                'finding' => $redirectsToHttps
                    ? Finding::pass('http_upgrade', 'http', 3)
                    : Finding::warn('http_upgrade', 'http', 3),
                'result' => $probe,
                'details' => $details,
            ];
        }

        // The user scanned plain HTTP. If that request already redirected to
        // HTTPS, the upgrade is correct; otherwise HTTPS exists but is not
        // enforced.
        if ($primary->finalTarget->isHttps()) {
            return [
                'finding' => Finding::pass('http_upgrade', 'http', 3),
                'result' => $probe,
                'details' => $details,
            ];
        }

        return [
            'finding' => Finding::warn('http_upgrade', 'http', 3),
            'result' => $probe,
            'details' => $details,
        ];
    }

    private function hostPort(string $host, int $port): string
    {
        $formatted = str_contains($host, ':') ? '['.$host.']' : $host;

        return $formatted.':'.$port;
    }

    /**
     * @param  array<string, list<string>>  $headers
     * @return array<string, list<string>>
     */
    private function relevantHeaders(array $headers): array
    {
        $keep = [
            'content-type', 'content-length', 'content-encoding', 'cache-control',
            'strict-transport-security', 'content-security-policy', 'x-content-type-options',
            'x-frame-options', 'referrer-policy', 'permissions-policy', 'server',
            'x-powered-by', 'x-generator', 'x-aspnet-version', 'x-robots-tag',
            'set-cookie',
        ];

        return array_intersect_key($headers, array_flip($keep));
    }

    private function short(string $value): string
    {
        $value = trim($value);

        return $value === '' ? 'none' : mb_substr($value, 0, 80);
    }
}
