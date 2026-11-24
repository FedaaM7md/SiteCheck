<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Parsers;

final class HeaderAnalysis
{
    /**
     * @param  list<array{name: string, secure: bool, http_only: bool, same_site: ?string}>  $cookies
     */
    public function __construct(
        public readonly bool $hstsPresent,
        public readonly ?int $hstsMaxAge,
        public readonly bool $hstsIncludeSubdomains,
        public readonly bool $hstsPreload,
        public readonly bool $cspPresent,
        public readonly bool $cspUnsafeInline,
        public readonly bool $cspUnsafeEval,
        public readonly bool $cspFrameAncestors,
        public readonly bool $xContentTypeOptions,
        public readonly ?string $xFrameOptions,
        public readonly ?string $frameAncestors,
        public readonly ?string $referrerPolicy,
        public readonly bool $permissionsPolicy,
        public readonly array $cookies,
        public readonly ?string $server,
        public readonly ?string $poweredBy,
        public readonly ?string $generator,
        public readonly ?string $aspNetVersion,
        public readonly ?string $xRobotsTag,
        public readonly ?string $contentType,
        public readonly ?string $contentEncoding,
        public readonly ?string $contentLength,
        public readonly ?string $cacheControl,
        public readonly ?string $cspValue,
        public readonly ?string $hstsValue,
    ) {}
}
