<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Dto;

use App\Domain\SiteCheck\Scoring\ScoreCalculator;

final class SectionResult
{
    /**
     * @param  list<Finding>  $findings
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly string $key,
        public readonly array $findings,
        public readonly array $details = [],
        public readonly ?string $error = null,
    ) {}

    public function score(): int
    {
        return ScoreCalculator::scoreFindings($this->findings);
    }

    public function grade(): string
    {
        return ScoreCalculator::grade($this->score());
    }

    /** @return array<string, mixed> */
    public function toArray(bool $localize = false): array
    {
        return [
            'key' => $this->key,
            'score' => $this->score(),
            'grade' => $this->grade(),
            'error' => $this->error,
            'findings' => array_map(
                static fn (Finding $finding): array => $finding->toArray($localize),
                $this->findings,
            ),
            'details' => $this->details,
        ];
    }

    public function withError(string $error): self
    {
        return new self($this->key, $this->findings, $this->details, $error);
    }
}
