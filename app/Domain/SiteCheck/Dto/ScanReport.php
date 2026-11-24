<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Dto;

use App\Domain\SiteCheck\Scoring\ScoreCalculator;

final class ScanReport
{
    /**
     * @param  array<string, SectionResult>  $sections
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $input,
        public readonly string $host,
        public readonly string $url,
        public readonly array $sections,
        public readonly array $meta = [],
        public readonly int $durationMs = 0,
        public readonly ?int $scannedAt = null,
    ) {}

    public function overallScore(): int
    {
        $scores = [];
        foreach ($this->sections as $key => $section) {
            $scores[$key] = $section->score();
        }

        /** @var array<string, int> $weights */
        $weights = config('sitecheck.section_weights', [
            'dns' => 15,
            'ssl' => 30,
            'email' => 15,
            'http' => 25,
            'seo' => 15,
        ]);

        return ScoreCalculator::overall($scores, $weights);
    }

    public function overallGrade(): string
    {
        return ScoreCalculator::grade($this->overallScore());
    }

    public function section(string $key): ?SectionResult
    {
        return $this->sections[$key] ?? null;
    }

    /** @return array<string, mixed> */
    public function toArray(bool $localize = false): array
    {
        $sections = [];
        foreach ($this->sections as $key => $section) {
            $sections[$key] = $section->toArray($localize);
        }

        return [
            'schema' => 1,
            'generated_at' => gmdate('c', $this->scannedAt ?? time()),
            'duration_ms' => $this->durationMs,
            'target' => [
                'input' => $this->input,
                'host' => $this->host,
                'url' => $this->url,
            ],
            'overall' => [
                'score' => $this->overallScore(),
                'grade' => $this->overallGrade(),
                'tone' => ScoreCalculator::tone($this->overallScore()),
            ],
            'sections' => $sections,
            'meta' => $this->meta,
        ];
    }
}
