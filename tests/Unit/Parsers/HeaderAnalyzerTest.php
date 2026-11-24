<?php

declare(strict_types=1);

namespace Tests\Unit\Parsers;

use App\Domain\SiteCheck\Parsers\HeaderAnalyzer;
use PHPUnit\Framework\TestCase;

final class HeaderAnalyzerTest extends TestCase
{
    public function test_parses_a_hardened_response(): void
    {
        $analysis = (new HeaderAnalyzer)->analyze([
            'strict-transport-security' => ['max-age=31536000; includeSubDomains; preload'],
            'content-security-policy' => ["default-src 'self'; script-src 'self'; frame-ancestors 'none'"],
            'x-content-type-options' => ['nosniff'],
            'x-frame-options' => ['DENY'],
            'referrer-policy' => ['strict-origin-when-cross-origin'],
            'permissions-policy' => ['camera=(), microphone=()'],
            'server' => ['nginx'],
            'content-type' => ['text/html; charset=utf-8'],
            'content-encoding' => ['gzip'],
            'set-cookie' => [
                'session=abc; Secure; HttpOnly; SameSite=Lax',
                'theme=dark; SameSite=None',
            ],
        ]);

        $this->assertTrue($analysis->hstsPresent);
        $this->assertSame(31536000, $analysis->hstsMaxAge);
        $this->assertTrue($analysis->hstsIncludeSubdomains);
        $this->assertTrue($analysis->hstsPreload);
        $this->assertTrue($analysis->cspPresent);
        $this->assertFalse($analysis->cspUnsafeInline);
        $this->assertTrue($analysis->cspFrameAncestors);
        $this->assertSame("'none'", $analysis->frameAncestors);
        $this->assertTrue($analysis->xContentTypeOptions);
        $this->assertSame('DENY', $analysis->xFrameOptions);
        $this->assertSame('strict-origin-when-cross-origin', $analysis->referrerPolicy);
        $this->assertTrue($analysis->permissionsPolicy);
        $this->assertSame('nginx', $analysis->server);
        $this->assertCount(2, $analysis->cookies);
        $this->assertTrue($analysis->cookies[0]['secure']);
        $this->assertTrue($analysis->cookies[0]['http_only']);
        $this->assertSame('lax', $analysis->cookies[0]['same_site']);
        $this->assertFalse($analysis->cookies[1]['secure']);
        $this->assertSame('none', $analysis->cookies[1]['same_site']);
        $this->assertSame('gzip', $analysis->contentEncoding);
    }

    public function test_detects_unsafe_csp(): void
    {
        $analysis = (new HeaderAnalyzer)->analyze([
            'content-security-policy' => ["default-src 'self'; script-src 'unsafe-inline' 'unsafe-eval'"],
        ]);

        $this->assertTrue($analysis->cspUnsafeInline);
        $this->assertTrue($analysis->cspUnsafeEval);
        $this->assertFalse($analysis->cspFrameAncestors);
    }

    public function test_missing_headers_are_empty(): void
    {
        $analysis = (new HeaderAnalyzer)->analyze([]);

        $this->assertFalse($analysis->hstsPresent);
        $this->assertNull($analysis->hstsMaxAge);
        $this->assertFalse($analysis->cspPresent);
        $this->assertFalse($analysis->xContentTypeOptions);
        $this->assertNull($analysis->xFrameOptions);
        $this->assertNull($analysis->referrerPolicy);
        $this->assertFalse($analysis->permissionsPolicy);
        $this->assertSame([], $analysis->cookies);
        $this->assertNull($analysis->server);
    }

    public function test_hsts_without_max_age_is_flagged_by_missing_value(): void
    {
        $analysis = (new HeaderAnalyzer)->analyze([
            'strict-transport-security' => ['includeSubDomains'],
        ]);

        $this->assertTrue($analysis->hstsPresent);
        $this->assertNull($analysis->hstsMaxAge);
        $this->assertTrue($analysis->hstsIncludeSubdomains);
    }

    public function test_server_banner_fields_are_exposed(): void
    {
        $analysis = (new HeaderAnalyzer)->analyze([
            'server' => ['Apache/2.4.58 (Win64)'],
            'x-powered-by' => ['PHP/8.4.28'],
            'x-aspnet-version' => ['4.0.30319'],
            'x-generator' => ['WordPress 6.8'],
            'x-robots-tag' => ['noindex'],
        ]);

        $this->assertSame('Apache/2.4.58 (Win64)', $analysis->server);
        $this->assertSame('PHP/8.4.28', $analysis->poweredBy);
        $this->assertSame('4.0.30319', $analysis->aspNetVersion);
        $this->assertSame('WordPress 6.8', $analysis->generator);
        $this->assertSame('noindex', $analysis->xRobotsTag);
    }

    public function test_malformed_set_cookie_is_ignored(): void
    {
        $analysis = (new HeaderAnalyzer)->analyze([
            'set-cookie' => ['broken-cookie', '=noname; Secure', 'ok=1; Secure'],
        ]);

        $this->assertCount(1, $analysis->cookies);
        $this->assertSame('ok', $analysis->cookies[0]['name']);
    }
}
