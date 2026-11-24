<?php

declare(strict_types=1);

namespace App\Support\Network;

/**
 * Real resolver based on PHP's synchronous DNS functions. It never follows
 * CNAMEs itself: the resolver of the operating system does that.
 */
final class SystemHostResolver implements HostResolver
{
    public function resolve(string $host): array
    {
        if (IpAddress::parse($host) !== null) {
            return [$host];
        }

        $host = rtrim(strtolower($host), '.');
        $ips = [];

        $a = @dns_get_record($host, DNS_A);
        if (is_array($a)) {
            foreach ($a as $record) {
                if (isset($record['ip']) && is_string($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }

        $aaaa = @dns_get_record($host, DNS_AAAA);
        if (is_array($aaaa)) {
            foreach ($aaaa as $record) {
                if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        if ($ips === []) {
            $fallback = @gethostbynamel($host);
            if (is_array($fallback)) {
                $ips = $fallback;
            }
        }

        $ips = array_values(array_unique(array_filter(
            $ips,
            static fn (string $ip): bool => IpAddress::parse($ip) !== null,
        )));

        if ($ips === []) {
            throw HostResolutionException::forHost($host);
        }

        return $ips;
    }
}
