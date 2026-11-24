<?php

declare(strict_types=1);

namespace Tests\Unit\Parsers;

use App\Domain\SiteCheck\Parsers\DmarcParser;
use PHPUnit\Framework\TestCase;

final class DmarcParserTest extends TestCase
{
    private DmarcParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DmarcParser;
    }

    public function test_parses_a_strict_policy(): void
    {
        $result = $this->parser->parse([
            'v=DMARC1; p=reject; sp=quarantine; rua=mailto:dmarc@example.com,mailto:backup@example.com; pct=100; adkim=s',
        ]);

        $this->assertTrue($result->present);
        $this->assertTrue($result->valid);
        $this->assertSame('reject', $result->policy);
        $this->assertSame('quarantine', $result->subdomainPolicy);
        $this->assertCount(2, $result->rua);
        $this->assertSame(100, $result->pct);
        $this->assertSame([], $result->issues);
    }

    public function test_flags_monitoring_policy(): void
    {
        $result = $this->parser->parse(['v=DMARC1; p=none; rua=mailto:dmarc@example.com']);

        $this->assertTrue($result->valid);
        $this->assertSame('none', $result->policy);
        $this->assertTrue($result->hasIssue('policy_none'));
    }

    public function test_flags_missing_policy(): void
    {
        $result = $this->parser->parse(['v=DMARC1; rua=mailto:dmarc@example.com']);

        $this->assertTrue($result->present);
        $this->assertFalse($result->valid);
        $this->assertTrue($result->hasIssue('missing_policy'));
    }

    public function test_flags_invalid_policy(): void
    {
        $result = $this->parser->parse(['v=DMARC1; p=maybe']);

        $this->assertTrue($result->hasIssue('invalid_policy'));
        $this->assertFalse($result->valid);
    }

    public function test_flags_multiple_records(): void
    {
        $result = $this->parser->parse([
            'v=DMARC1; p=reject; rua=mailto:a@example.com',
            'v=DMARC1; p=none',
        ]);

        $this->assertTrue($result->hasIssue('multiple_records'));
        $this->assertFalse($result->valid);
    }

    public function test_flags_missing_rua_and_partial_pct(): void
    {
        $result = $this->parser->parse(['v=DMARC1; p=quarantine; pct=50']);

        $this->assertTrue($result->hasIssue('no_rua'));
        $this->assertTrue($result->hasIssue('pct_less_than_100'));
        $this->assertSame(50, $result->pct);
    }

    public function test_flags_invalid_pct(): void
    {
        $result = $this->parser->parse(['v=DMARC1; p=reject; pct=abc']);

        $this->assertTrue($result->hasIssue('invalid_pct'));
        $this->assertFalse($result->valid);
    }

    public function test_flags_duplicate_tags(): void
    {
        $result = $this->parser->parse(['v=DMARC1; p=reject; p=none']);

        $this->assertTrue($result->hasIssue('duplicate_tag'));
    }

    public function test_missing_record(): void
    {
        $result = $this->parser->parse(['v=spf1 -all']);

        $this->assertFalse($result->present);
        $this->assertSame([], $result->rua);
    }

    public function test_invalid_record_is_reported(): void
    {
        $result = $this->parser->parse(['v=DMARC1x; p=reject']);

        $this->assertFalse($result->present);
        $this->assertTrue($result->hasIssue('invalid_record'));
    }
}
