<?php

declare(strict_types=1);

namespace Tests\Unit\Parsers;

use App\Domain\SiteCheck\Parsers\SpfParser;
use PHPUnit\Framework\TestCase;

final class SpfParserTest extends TestCase
{
    private SpfParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SpfParser;
    }

    public function test_parses_a_typical_record(): void
    {
        $result = $this->parser->parse([
            'v=spf1 include:_spf.google.com ip4:93.184.216.34 -all',
        ]);

        $this->assertTrue($result->present);
        $this->assertTrue($result->valid);
        $this->assertSame('-', $result->allQualifier);
        $this->assertSame(1, $result->lookupCount);
        $this->assertSame([], $result->issues);
    }

    public function test_detects_plus_all(): void
    {
        $result = $this->parser->parse(['v=spf1 +all']);

        $this->assertTrue($result->hasIssue('plus_all'));
        $this->assertSame('+', $result->allQualifier);
    }

    public function test_detects_softfail_and_neutral(): void
    {
        $this->assertTrue($this->parser->parse(['v=spf1 ~all'])->hasIssue('softfail_all'));
        $this->assertTrue($this->parser->parse(['v=spf1 ?all'])->hasIssue('neutral_all'));
    }

    public function test_detects_missing_all(): void
    {
        $result = $this->parser->parse(['v=spf1 include:_spf.example.com']);

        $this->assertTrue($result->hasIssue('missing_all'));
        $this->assertNull($result->allQualifier);
    }

    public function test_detects_multiple_records(): void
    {
        $result = $this->parser->parse([
            'v=spf1 -all',
            'v=spf1 include:_spf.example.com -all',
        ]);

        $this->assertTrue($result->hasIssue('multiple_records'));
        $this->assertFalse($result->valid);
        $this->assertCount(2, $result->records);
    }

    public function test_counts_lookups_and_flags_above_ten(): void
    {
        $terms = [];
        for ($i = 1; $i <= 11; $i++) {
            $terms[] = 'include:spf'.$i.'.example.com';
        }

        $result = $this->parser->parse(['v=spf1 '.implode(' ', $terms).' -all']);

        $this->assertSame(11, $result->lookupCount);
        $this->assertTrue($result->hasIssue('too_many_lookups'));
    }

    public function test_counts_a_mx_ptr_exists_and_redirect(): void
    {
        $result = $this->parser->parse([
            'v=spf1 a mx ptr exists:%{i}.spf.example.com include:_spf.example.com redirect=_spf2.example.com -all',
        ]);

        $this->assertSame(6, $result->lookupCount);
        $this->assertTrue($result->hasIssue('ptr_mechanism'));
        $this->assertTrue($result->hasIssue('redirect_ignored'));
        $this->assertTrue($result->hasRedirect);
        $this->assertSame('_spf2.example.com', $result->redirectTarget);
    }

    public function test_detects_unknown_mechanism_and_modifier(): void
    {
        $result = $this->parser->parse(['v=spf1 madeup:example.com foo=bar -all']);

        $this->assertTrue($result->hasIssue('unknown_mechanism'));
        $this->assertTrue($result->hasIssue('unknown_modifier'));
        $this->assertFalse($result->valid);
    }

    public function test_missing_record(): void
    {
        $result = $this->parser->parse(['google-site-verification=abc']);

        $this->assertFalse($result->present);
        $this->assertFalse($result->valid);
    }

    public function test_is_case_insensitive_and_ignores_unrelated_txt(): void
    {
        $result = $this->parser->parse([
            'some other txt',
            'V=SPF1 IP4:93.184.216.34 -ALL',
        ]);

        $this->assertTrue($result->present);
        $this->assertSame('-', $result->allQualifier);
    }
}
