<?php

declare(strict_types=1);

namespace App\Support\Network\Dns;

interface DnsClient
{
    /**
     * Return normalised records of one type for a hostname.
     *
     * Supported types: A, AAAA, CNAME, NS, MX, TXT, CAA, SOA.
     *
     * @return list<array<string, mixed>>
     */
    public function records(string $host, string $type): array;

    /** Whether CAA lookups are supported by this platform/resolver setup. */
    public function canResolveCaa(): bool;

    /** Human-readable resolver status, e.g. "ok", "timeout" or "error". */
    public function status(): string;
}
