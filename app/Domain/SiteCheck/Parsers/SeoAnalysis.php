<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Parsers;

final class SeoAnalysis
{
    /**
     * @param  list<string>  $h1Texts
     * @param  array<string, string>  $openGraph
     */
    public function __construct(
        public readonly bool $parsed,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $canonical,
        public readonly ?string $robotsMeta,
        public readonly int $h1Count,
        public readonly array $h1Texts,
        public readonly ?string $lang,
        public readonly ?string $viewport,
        public readonly ?string $charset,
        public readonly array $openGraph,
        public readonly int $imagesTotal,
        public readonly int $imagesMissingAlt,
        public readonly int $imagesEmptyAlt,
        public readonly int $linksTotal,
    ) {}

    public static function unparsed(): self
    {
        return new self(false, null, null, null, null, 0, [], null, null, null, [], 0, 0, 0, 0);
    }
}
