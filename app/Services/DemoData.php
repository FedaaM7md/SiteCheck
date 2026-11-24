<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Services;

use App\Domain\SiteCheck\Scoring\ScoreCalculator;
use App\Models\Scan;
use Illuminate\Support\Carbon;

/**
 * Realistic, clearly fictional sample history for the demo build.
 *
 * Every row is owned by a dedicated token so history can show real visitor
 * scans alongside sample rows without mixing ownership. Hosts use the
 * reserved `.example` TLD and infrastructure values use the documentation
 * ranges, so nothing here can be mistaken for a real company.
 */
final class DemoData
{
    public const OWNER_TOKEN = 'demo0000000000000000000000000000';

    /**
     * @var list<array{host: string, score: int, days: int}>
     */
    private const SITES = [
        ['host' => 'nour-clinic.example', 'score' => 94, 'days' => 2],
        ['host' => 'beit-elzaytoun.example', 'score' => 88, 'days' => 5],
        ['host' => 'nile-market.example', 'score' => 82, 'days' => 9],
        ['host' => 'cairo-motors.example', 'score' => 76, 'days' => 13],
        ['host' => 'al-shifa-pharmacy.example', 'score' => 91, 'days' => 17],
        ['host' => 'madrasty.example', 'score' => 68, 'days' => 21],
        ['host' => 'sahara-travel.example', 'score' => 85, 'days' => 26],
        ['host' => 'delta-furniture.example', 'score' => 73, 'days' => 30],
        ['host' => 'giza-legal.example', 'score' => 79, 'days' => 35],
        ['host' => 'suez-logistics.example', 'score' => 62, 'days' => 40],
        ['host' => 'alex-dental.example', 'score' => 87, 'days' => 45],
        ['host' => 'mansoura-bakery.example', 'score' => 55, 'days' => 50],
        ['host' => 'tanta-textiles.example', 'score' => 71, 'days' => 55],
        ['host' => 'aswan-tours.example', 'score' => 83, 'days' => 60],
        ['host' => 'smart-cairo.example', 'score' => 90, 'days' => 65],
        ['host' => 'redsea-diving.example', 'score' => 66, 'days' => 70],
        ['host' => 'pyramids-realestate.example', 'score' => 58, 'days' => 76],
        ['host' => 'online-academy.example', 'score' => 77, 'days' => 82],
        ['host' => 'green-valley-farms.example', 'score' => 64, 'days' => 88],
        ['host' => 'heliopolis-fitness.example', 'score' => 81, 'days' => 94],
        ['host' => 'new-cairo-clinic.example', 'score' => 93, 'days' => 100],
        ['host' => 'luxor-hotels.example', 'score' => 52, 'days' => 106],
        ['host' => 'port-said-export.example', 'score' => 60, 'days' => 112],
        ['host' => 'maadi-cafe.example', 'score' => 84, 'days' => 118],
    ];

    public static function ownerToken(): string
    {
        return self::OWNER_TOKEN;
    }

    public static function isDemoToken(?string $token): bool
    {
        return $token === self::OWNER_TOKEN;
    }

    /**
     * Insert the sample scans. Existing sample rows are replaced first.
     *
     * @return int number of rows created
     */
    public function seed(): int
    {
        Scan::query()->where('owner_token', self::OWNER_TOKEN)->delete();

        $created = 0;

        // Seed oldest first so the highest id is the most recent scan.
        foreach (array_reverse(self::SITES) as $site) {
            $this->createScan($site['host'], $site['score'], $site['days']);
            $created++;
        }

        return $created;
    }

    private function createScan(string $host, int $score, int $days): void
    {
        $report = $this->report($host, $score);
        $scannedAt = Carbon::now()
            ->subDays($days)
            ->setTime(9 + ($days % 9), ($days * 7) % 60);

        Scan::create([
            'owner_token' => self::OWNER_TOKEN,
            'input' => $host,
            'host' => $host,
            'url' => 'https://'.$host.'/',
            'overall_score' => $score,
            'grade' => ScoreCalculator::grade($score),
            'section_scores' => array_map(
                static fn (array $section): int => $section['score'],
                $report['sections'],
            ),
            'report' => $report,
            'locale' => 'ar',
            'duration_ms' => 1800 + (($days * 311) % 6400),
            'scanned_at' => $scannedAt,
        ]);
    }

    /** @return array<string, mixed> */
    private function report(string $host, int $score): array
    {
        $offsets = ['dns' => 3, 'ssl' => 2, 'email' => -4, 'http' => -6, 'seo' => -2];
        $sections = [];

        foreach ($offsets as $key => $offset) {
            $sectionScore = max(0, min(100, $score + $offset));
            $sections[$key] = $this->section($key, $host, $sectionScore);
        }

        return [
            'schema' => 1,
            'generated_at' => gmdate('c'),
            'duration_ms' => 3200,
            'target' => [
                'input' => $host,
                'host' => $host,
                'url' => 'https://'.$host.'/',
            ],
            'overall' => [
                'score' => $score,
                'grade' => ScoreCalculator::grade($score),
                'tone' => ScoreCalculator::tone($score),
            ],
            'sections' => $sections,
            'meta' => [
                'pinned_ip' => '203.0.113.'.(10 + ($score % 40)),
                'resolved_ips' => ['203.0.113.'.(10 + ($score % 40))],
                'tool' => 'SiteCheck',
                'tool_version' => '2.0.0',
                'dkim_selectors' => [],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function section(string $key, string $host, int $score): array
    {
        return [
            'key' => $key,
            'score' => $score,
            'grade' => ScoreCalculator::grade($score),
            'error' => null,
            'findings' => $this->findings($key, $host, $score),
            'details' => $this->details($key, $host, $score),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function findings(string $key, string $host, int $score): array
    {
        $findings = [];

        if ($score >= 68) {
            $findings[] = $this->finding($key, $this->passId($key), 'pass', $this->passParams($host));
            if ($score < 92) {
                $findings[] = $this->finding($key, $this->warnId($key), 'warn', $this->warnParams($key, $host, $score));
            }
        } else {
            $findings[] = $this->finding($key, $this->failId($key), 'fail', $this->failParams($host));
            $findings[] = $this->finding($key, $this->warnId($key), 'warn', $this->warnParams($key, $host, $score));
        }

        return $findings;
    }

    private function passId(string $key): string
    {
        return match ($key) {
            'dns' => 'dns_a',
            'ssl' => 'ssl_verified',
            'email' => 'email_dmarc_present',
            'http' => 'http_status',
            default => 'seo_title',
        };
    }

    private function warnId(string $key): string
    {
        return match ($key) {
            'dns' => 'dns_caa',
            'ssl' => 'ssl_expiry',
            'email' => 'email_dkim',
            'http' => 'http_csp',
            default => 'seo_sitemap',
        };
    }

    private function failId(string $key): string
    {
        return match ($key) {
            'dns' => 'dns_a',
            'ssl' => 'ssl_hostname',
            'email' => 'email_spf_present',
            'http' => 'http_https',
            default => 'seo_h1',
        };
    }

    /** @return array<string, string|int|float> */
    private function passParams(string $host): array
    {
        return ['status' => 200, 'length' => 54];
    }

    /** @return array<string, string|int|float> */
    private function warnParams(string $key, string $host, int $score): array
    {
        return match ($key) {
            'ssl' => ['days' => $score < 75 ? 12 : 34],
            'email' => ['working' => 0, 'total' => 2],
            default => [],
        };
    }

    /** @return array<string, string|int|float> */
    private function failParams(string $host): array
    {
        return ['host' => $host, 'count' => 0];
    }

    /**
     * @param  array<string, string|int|float>  $params
     * @return array<string, mixed>
     */
    private function finding(string $section, string $id, string $severity, array $params): array
    {
        return [
            'id' => $id,
            'section' => $section,
            'severity' => $severity,
            'weight' => 1,
            'params' => $params,
            'data' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function details(string $key, string $host, int $score): array
    {
        $https = $score >= 68;

        return match ($key) {
            'dns' => [
                'a' => $score >= 68 ? [['ip' => '203.0.113.'.(20 + ($score % 50))]] : [],
                'aaaa' => [],
                'cname' => [],
                'ns' => $score >= 68
                    ? [['target' => 'ns1.example.net'], ['target' => 'ns2.example.net']]
                    : [],
                'mx' => [
                    ['priority' => 10, 'target' => 'mx1.example.net'],
                    ['priority' => 20, 'target' => 'mx2.example.net'],
                ],
                'txt' => $https
                    ? [['text' => 'v=spf1 include:_spf.example.net ~all']]
                    : [],
                'caa' => $score >= 92
                    ? [['tag' => 'issue', 'flags' => 0, 'value' => 'letsencrypt.org']]
                    : [],
                'soa' => [['mname' => 'ns1.example.net', 'rname' => 'hostmaster.example.net']],
            ],
            'ssl' => [
                'tls' => [
                    'verified' => $score >= 68,
                    'protocol' => 'TLSv1.3',
                    'cipher' => 'TLS_AES_256_GCM_SHA384',
                    'cipher_bits' => 256,
                    'chain_count' => 3,
                    'handshake_ms' => 140,
                    'error' => null,
                    'certificate' => [
                        'subject' => 'CN='.($score >= 68 ? $host : 'other.example'),
                        'issuer' => 'CN=R3, O=Let\'s Encrypt',
                        'valid_from' => Carbon::now()->subDays(40)->timestamp,
                        'valid_to' => Carbon::now()->addDays($score < 75 ? 12 : 34)->timestamp,
                        'dns_names' => $score >= 68 ? [$host, 'www.'.$host] : ['other.example'],
                    ],
                ],
            ],
            'email' => [
                'spf' => [
                    'records' => $https ? ['v=spf1 include:_spf.example.net ~all'] : [],
                    'lookups' => $https ? 4 : 0,
                    'all' => $https ? '~all' : null,
                    'issues' => [],
                ],
                'dmarc' => [
                    'present' => $https,
                    'policy' => $https ? 'quarantine' : null,
                    'pct' => $https ? 100 : null,
                    'rua' => $https ? ['mailto:dmarc@example.net'] : [],
                    'tags' => $https ? ['p' => 'quarantine'] : [],
                ],
                'dkim' => [
                    'selectors' => [
                        [
                            'selector' => 'google',
                            'valid' => $score >= 92,
                            'revoked' => false,
                            'key_bits' => $score >= 92 ? 2048 : 0,
                        ],
                    ],
                ],
                'mx' => [
                    ['priority' => 10, 'target' => 'mx1.example.net'],
                    ['priority' => 20, 'target' => 'mx2.example.net'],
                ],
            ],
            'http' => [
                'final_url' => ($https ? 'https' : 'http').'://'.$host.'/',
                'status' => 200,
                'total_time_ms' => 320 + ($score % 400),
                'server' => 'nginx',
                'powered_by' => null,
                'redirects' => $https ? [['status' => 301, 'url' => 'https://'.$host.'/']] : [],
                'headers' => array_filter([
                    'strict-transport-security' => $https ? ['max-age=31536000; includeSubDomains'] : null,
                    'content-security-policy' => $score >= 92 ? ["default-src 'self'"] : null,
                    'x-content-type-options' => ['nosniff'],
                    'x-frame-options' => ['DENY'],
                    'referrer-policy' => ['strict-origin-when-cross-origin'],
                ]),
                'cookies' => [
                    ['name' => 'session', 'secure' => $https, 'http_only' => true, 'same_site' => 'Lax'],
                ],
            ],
            'seo' => [
                'page' => [
                    'title' => $host.' — صفحة رئيسية',
                    'description' => 'خدمة مصرية تشرح ما تقدمه بوضوح وتسهّل التواصل والطلب.',
                    'canonical' => 'https://'.$host.'/',
                    'robots_meta' => 'index,follow',
                    'lang' => 'ar',
                    'viewport' => 'width=device-width, initial-scale=1',
                    'h1_count' => $score >= 68 ? 1 : 0,
                    'images_total' => 12,
                    'images_missing_alt' => $score >= 75 ? 0 : 3,
                    'open_graph' => [
                        'og:title' => $host,
                        'og:description' => 'خدمة مصرية تشرح ما تقدمه بوضوح.',
                    ],
                ],
                'robots_txt' => ['available' => true, 'status' => 200],
                'sitemap' => ['available' => $score >= 92, 'status' => $score >= 92 ? 200 : 404],
            ],
        };
    }
}
