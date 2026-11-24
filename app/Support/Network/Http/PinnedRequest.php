<?php

declare(strict_types=1);

namespace App\Support\Network\Http;

final class PinnedRequest
{
    /**
     * @param  list<string>  $headers
     * @param  list<string>  $fallbackIps  already-validated alternative addresses
     */
    public function __construct(
        public readonly string $url,
        public readonly string $host,
        public readonly int $port,
        public readonly string $ip,
        public readonly int $timeoutSeconds,
        public readonly int $connectTimeoutSeconds,
        public readonly int $maxBodyBytes,
        public readonly int $maxHeaderBytes,
        public readonly string $userAgent,
        public readonly array $headers = [],
        public readonly array $fallbackIps = [],
    ) {}
}
