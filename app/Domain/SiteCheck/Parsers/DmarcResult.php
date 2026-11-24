<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Parsers;

final class DmarcResult
{
    /**
     * @param  list<string>  $rua
     * @param  list<string>  $issues
     * @param  array<string, string>  $tags
     */
    public function __construct(
        public readonly bool $present,
        public readonly bool $valid,
        public readonly ?string $policy,
        public readonly ?string $subdomainPolicy,
        public readonly array $rua,
        public readonly int $pct,
        public readonly array $tags,
        public readonly array $issues,
    ) {}

    public static function missing(): self
    {
        return new self(false, false, null, null, [], 100, [], []);
    }

    public function hasIssue(string $issue): bool
    {
        return in_array($issue, $this->issues, true);
    }
}
