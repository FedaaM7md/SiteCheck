<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Parsers;

final class SpfResult
{
    /**
     * @param  list<string>  $records
     * @param  array<string, string|null>  $mechanisms  mechanism => qualifier
     * @param  list<string>  $issues
     */
    public function __construct(
        public readonly bool $present,
        public readonly array $records,
        public readonly bool $valid,
        public readonly ?string $allQualifier,
        public readonly int $lookupCount,
        public readonly bool $hasRedirect,
        public readonly ?string $redirectTarget,
        public readonly array $mechanisms,
        public readonly array $issues,
    ) {}

    public static function missing(): self
    {
        return new self(false, [], false, null, 0, false, null, [], []);
    }

    public function hasIssue(string $issue): bool
    {
        return in_array($issue, $this->issues, true);
    }
}
