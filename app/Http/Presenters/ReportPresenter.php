<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Http\Presenters;

use App\Domain\SiteCheck\Dto\Finding;
use App\Domain\SiteCheck\Scoring\ScoreCalculator;

final class ReportPresenter
{
    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public function present(array $report): array
    {
        $sections = [];

        /** @var array<string, int> $weights */
        $weights = config('sitecheck.section_weights', []);

        foreach (array_keys($weights) as $key) {
            $raw = $report['sections'][$key] ?? null;
            if (! is_array($raw)) {
                continue;
            }

            $findings = [];
            $counts = ['pass' => 0, 'warn' => 0, 'fail' => 0, 'info' => 0];

            foreach ($raw['findings'] ?? [] as $rawFinding) {
                if (! is_array($rawFinding)) {
                    continue;
                }

                $finding = new Finding(
                    (string) ($rawFinding['id'] ?? ''),
                    (string) ($rawFinding['section'] ?? $key),
                    (string) ($rawFinding['severity'] ?? Finding::INFO),
                    (int) ($rawFinding['weight'] ?? 0),
                    is_array($rawFinding['params'] ?? null) ? $rawFinding['params'] : [],
                    is_array($rawFinding['data'] ?? null) ? $rawFinding['data'] : [],
                );

                $severity = $finding->severity;
                if (isset($counts[$severity])) {
                    $counts[$severity]++;
                }

                $findings[] = $finding->toArray(true) + [
                    'severity_label' => (string) __('sitecheck.severity.'.$severity),
                    'icon' => $this->iconFor($severity),
                ];
            }

            $score = (int) ($raw['score'] ?? 0);

            $sections[$key] = [
                'key' => $key,
                'title' => (string) __('sitecheck.sections.'.$key.'.title'),
                'description' => (string) __('sitecheck.sections.'.$key.'.description'),
                'score' => $score,
                'grade' => (string) ($raw['grade'] ?? ScoreCalculator::grade($score)),
                'tone' => ScoreCalculator::tone($score),
                'error' => isset($raw['error']) && $raw['error'] !== null
                    ? (string) __('sitecheck.report.section_error')
                    : null,
                'findings' => $findings,
                'counts' => $counts,
                'details' => is_array($raw['details'] ?? null) ? $raw['details'] : [],
            ];
        }

        $overallScore = (int) ($report['overall']['score'] ?? 0);
        $overallGrade = (string) ($report['overall']['grade'] ?? ScoreCalculator::grade($overallScore));

        return [
            'target' => is_array($report['target'] ?? null) ? $report['target'] : [],
            'overall' => [
                'score' => $overallScore,
                'grade' => $overallGrade,
                'tone' => ScoreCalculator::tone($overallScore),
                'label' => (string) __('sitecheck.grades.'.$overallGrade.'.label'),
                'summary' => (string) __('sitecheck.grades.'.$overallGrade.'.summary'),
            ],
            'sections' => $sections,
            'meta' => is_array($report['meta'] ?? null) ? $report['meta'] : [],
            'duration_ms' => (int) ($report['duration_ms'] ?? 0),
            'generated_at' => (string) ($report['generated_at'] ?? ''),
            'totals' => [
                'pass' => array_sum(array_column(array_column($sections, 'counts'), 'pass')),
                'warn' => array_sum(array_column(array_column($sections, 'counts'), 'warn')),
                'fail' => array_sum(array_column(array_column($sections, 'counts'), 'fail')),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public function export(array $report): array
    {
        $localized = $report;

        foreach ($localized['sections'] ?? [] as $key => $section) {
            if (! is_array($section)) {
                continue;
            }

            foreach ($section['findings'] ?? [] as $index => $rawFinding) {
                if (! is_array($rawFinding)) {
                    continue;
                }

                $finding = new Finding(
                    (string) ($rawFinding['id'] ?? ''),
                    (string) ($rawFinding['section'] ?? $key),
                    (string) ($rawFinding['severity'] ?? Finding::INFO),
                    (int) ($rawFinding['weight'] ?? 0),
                    is_array($rawFinding['params'] ?? null) ? $rawFinding['params'] : [],
                    is_array($rawFinding['data'] ?? null) ? $rawFinding['data'] : [],
                );

                $localized['sections'][$key]['findings'][$index] = $finding->toArray(true);
            }
        }

        $localized['exported_at'] = gmdate('c');
        $localized['locale'] = app()->getLocale();
        $localized['generator'] = 'SiteCheck';

        return $localized;
    }

    private function iconFor(string $severity): string
    {
        return match ($severity) {
            Finding::PASS => 'check',
            Finding::WARN => 'alert',
            Finding::FAIL => 'x',
            default => 'info',
        };
    }
}
