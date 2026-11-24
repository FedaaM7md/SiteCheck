<?php

declare(strict_types=1);

namespace App\Support\Network;

interface HostResolver
{
    /**
     * Resolve a hostname to its IP addresses (IPv4 and IPv6).
     *
     * @return list<string>
     *
     * @throws HostResolutionException
     */
    public function resolve(string $host): array;
}
