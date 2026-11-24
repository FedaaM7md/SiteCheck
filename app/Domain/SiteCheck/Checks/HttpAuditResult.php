<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Checks;

use App\Domain\SiteCheck\Dto\SectionResult;
use App\Support\Network\Http\HttpFetchResult;

final class HttpAuditResult
{
    public function __construct(
        public readonly SectionResult $section,
        public readonly ?HttpFetchResult $primary = null,
        public readonly ?HttpFetchResult $plainHttp = null,
    ) {}
}
