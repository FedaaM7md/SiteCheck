<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Scoring;

use App\Domain\SiteCheck\Dto\Finding;

final class ScoreCalculator
{
    /** @param list<Finding> $findings */
    public static function scoreFindings(array $findings): int
    {
        $totalWeight = 0.0;
        $earned = 0.0;

        foreach ($findings as $finding) {
            if (! $finding->isScored()) {
                continue;
            }

            $totalWeight += $finding->weight;
            $earned += $finding->weight * $finding->factor();
        }

        if ($totalWeight <= 0.0) {
            return 0;
        }

        return (int) round(($earned / $totalWeight) * 100);
    }

    /**
     * @param  array<string, int>  $sectionScores
     * @param  array<string, int>  $weights
     */
    public static function overall(array $sectionScores, array $weights): int
    {
        $total = 0;
        $sum = 0;

        foreach ($weights as $section => $weight) {
            if (! isset($sectionScores[$section])) {
                continue;
            }

            $total += $sectionScores[$section] * $weight;
            $sum += $weight;
        }

        return $sum === 0 ? 0 : (int) round($total / $sum);
    }

    public static function grade(int $score): string
    {
        /** @var list<array{min: int, grade: string}> $grades */
        $grades = config('sitecheck.grades', [
            ['min' => 90, 'grade' => 'A'],
            ['min' => 80, 'grade' => 'B'],
            ['min' => 70, 'grade' => 'C'],
            ['min' => 60, 'grade' => 'D'],
            ['min' => 0, 'grade' => 'F'],
        ]);

        foreach ($grades as $grade) {
            if ($score >= $grade['min']) {
                return $grade['grade'];
            }
        }

        return 'F';
    }

    public static function tone(int $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 75 => 'good',
            $score >= 55 => 'fair',
            default => 'poor',
        };
    }
}
