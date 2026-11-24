<?php

declare(strict_types=1);

namespace App\Support\Network\Http;

final class FetchHop
{
    public function __construct(
        public readonly string $url,
        public readonly int $status,
        public readonly ?string $location,
        public readonly int $timeMs,
        public readonly string $ip,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'status' => $this->status,
            'location' => $this->location,
            'time_ms' => $this->timeMs,
            'ip' => $this->ip,
        ];
    }
}
