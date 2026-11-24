<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Domain\SiteCheck;

use App\Domain\SiteCheck\Checks\DnsCheck;
use App\Domain\SiteCheck\Checks\EmailCheck;
use App\Domain\SiteCheck\Checks\HttpCheck;
use App\Domain\SiteCheck\Checks\SeoCheck;
use App\Domain\SiteCheck\Checks\SslCheck;
use App\Domain\SiteCheck\Dto\Finding;
use App\Domain\SiteCheck\Dto\ScanInput;
use App\Domain\SiteCheck\Dto\ScanReport;
use App\Domain\SiteCheck\Dto\SectionResult;
use App\Support\Network\UnsafeUrlException;
use App\Support\Network\UrlGuard;
use App\Support\Network\UrlNormalizer;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ScanService
{
    public function __construct(
        private readonly UrlNormalizer $normalizer,
        private readonly UrlGuard $guard,
        private readonly DnsCheck $dnsCheck,
        private readonly SslCheck $sslCheck,
        private readonly EmailCheck $emailCheck,
        private readonly HttpCheck $httpCheck,
        private readonly SeoCheck $seoCheck,
    ) {}

    /**
     * @throws UnsafeUrlException when the target itself is not allowed
     */
    public function run(ScanInput $input): ScanReport
    {
        $startedAt = microtime(true);

        $url = $this->normalizer->normalize($input->raw);
        $target = $this->guard->validate($url);

        $sections = [];

        $sections['dns'] = $this->safely('dns', fn (): SectionResult => $this->dnsCheck->run($target->host));
        $sections['ssl'] = $this->safely('ssl', fn (): SectionResult => $this->sslCheck->run($target));
        $sections['email'] = $this->safely('email', fn (): SectionResult => $this->emailCheck->run($target->host, $input->dkimSelectors));

        $httpResult = null;
        try {
            $httpResult = $this->httpCheck->run($target);
        } catch (Throwable $exception) {
            Log::warning('SiteCheck HTTP check failed', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        if ($httpResult === null) {
            $sections['http'] = new SectionResult(
                'http',
                [Finding::fail('check_failed', 'http', 1, ['section' => 'http'])],
                [],
                'check_failed',
            );
        } else {
            $sections['http'] = $httpResult->section;
        }

        $sections['seo'] = $this->safely(
            'seo',
            fn (): SectionResult => $this->seoCheck->run($target, $httpResult?->primary),
        );

        $meta = [
            'pinned_ip' => $target->ip,
            'resolved_ips' => $target->resolvedIps,
            'scheme' => $target->scheme,
            'scanned_at' => gmdate('c'),
            'tool' => 'SiteCheck',
            'tool_version' => '2.0.0',
            'dkim_selectors' => $input->dkimSelectors,
        ];

        return new ScanReport(
            input: $input->raw,
            host: $target->host,
            url: $target->url,
            sections: $sections,
            meta: $meta,
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
            scannedAt: time(),
        );
    }

    /**
     * @param  callable(): SectionResult  $check
     */
    private function safely(string $section, callable $check): SectionResult
    {
        try {
            return $check();
        } catch (Throwable $exception) {
            Log::warning('SiteCheck section failed', [
                'section' => $section,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return new SectionResult(
                $section,
                [Finding::fail('check_failed', $section, 1, ['section' => $section])],
                [],
                'check_failed',
            );
        }
    }
}
