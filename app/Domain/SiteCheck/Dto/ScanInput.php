<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Dto;

final class ScanInput
{
    /** @param list<string> $dkimSelectors */
    public function __construct(
        public readonly string $raw,
        public readonly array $dkimSelectors = [],
    ) {}
}
