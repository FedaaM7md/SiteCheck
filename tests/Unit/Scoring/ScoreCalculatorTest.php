<?php

declare(strict_types=1);

namespace Tests\Unit\Scoring;

use App\Domain\SiteCheck\Dto\Finding;
use App\Domain\SiteCheck\Dto\SectionResult;
use App\Domain\SiteCheck\Scoring\ScoreCalculator;
use Tests\TestCase;

final class ScoreCalculatorTest extends TestCase
{
    public function test_all_passing_findings_score_one_hundred(): void
    {
        $score = ScoreCalculator::scoreFindings([
            Finding::pass('a', 'dns', 3),
            Finding::pass('b', 'dns', 1),
        ]);

        $this->assertSame(100, $score);
    }

    public function test_warnings_are_half_weight(): void
    {
        $score = ScoreCalculator::scoreFindings([
            Finding::pass('a', 'dns', 3),
            Finding::warn('b', 'dns', 1),
        ]);

        $this->assertSame(88, $score);
    }

    public function test_failures_remove_their_weight(): void
    {
        $score = ScoreCalculator::scoreFindings([
            Finding::pass('a', 'dns', 3),
            Finding::fail('b', 'dns', 1),
        ]);

        $this->assertSame(75, $score);
    }

    public function test_informational_findings_are_excluded(): void
    {
        $score = ScoreCalculator::scoreFindings([
            Finding::info('a', 'dns'),
            Finding::pass('b', 'dns', 2),
        ]);

        $this->assertSame(100, $score);
    }

    public function test_zero_weight_findings_are_excluded(): void
    {
        $score = ScoreCalculator::scoreFindings([
            Finding::pass('a', 'dns', 1),
            Finding::pass('b', 'dns', 0),
        ]);

        $this->assertSame(100, $score);
    }

    public function test_empty_findings_score_zero(): void
    {
        $this->assertSame(0, ScoreCalculator::scoreFindings([]));
    }

    public function test_section_result_uses_the_same_formula(): void
    {
        $section = new SectionResult('dns', [
            Finding::pass('a', 'dns', 1),
            Finding::fail('b', 'dns', 1),
        ]);

        $this->assertSame(50, $section->score());
        $this->assertSame('F', $section->grade());
    }

    public function test_overall_is_weighted_by_section(): void
    {
        $overall = ScoreCalculator::overall(
            ['dns' => 100, 'ssl' => 0, 'email' => 50],
            ['dns' => 15, 'ssl' => 30, 'email' => 15],
        );

        $this->assertSame(38, $overall);
    }

    public function test_overall_ignores_unknown_sections(): void
    {
        $overall = ScoreCalculator::overall(
            ['dns' => 100, 'made_up' => 0],
            ['dns' => 10],
        );

        $this->assertSame(100, $overall);
    }

    public function test_grades_follow_configured_thresholds(): void
    {
        $this->assertSame('A', ScoreCalculator::grade(90));
        $this->assertSame('A', ScoreCalculator::grade(100));
        $this->assertSame('B', ScoreCalculator::grade(89));
        $this->assertSame('C', ScoreCalculator::grade(79));
        $this->assertSame('D', ScoreCalculator::grade(69));
        $this->assertSame('F', ScoreCalculator::grade(0));
    }

    public function test_tones(): void
    {
        $this->assertSame('excellent', ScoreCalculator::tone(95));
        $this->assertSame('good', ScoreCalculator::tone(80));
        $this->assertSame('fair', ScoreCalculator::tone(60));
        $this->assertSame('poor', ScoreCalculator::tone(20));
    }
}
