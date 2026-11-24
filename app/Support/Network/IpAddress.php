<?php

declare(strict_types=1);

namespace App\Support\Network;

/**
 * Immutable, binary-backed representation of an IP address with a strict
 * public-routability policy. This class is the single source of truth for
 * the SSRF block lists, so it must stay free of network access.
 */
final class IpAddress
{
    /** @var array<string, int> */
    private const IPV4_BLOCKED = [
        '0.0.0.0' => 8,          // "this network"
        '10.0.0.0' => 8,         // private
        '100.64.0.0' => 10,      // CGNAT
        '127.0.0.0' => 8,        // loopback
        '169.254.0.0' => 16,     // link-local (incl. 169.254.169.254)
        '172.16.0.0' => 12,      // private
        '192.0.0.0' => 24,       // IETF protocol assignments
        '192.0.2.0' => 24,       // documentation TEST-NET-1
        '192.88.99.0' => 24,     // 6to4 relay anycast (deprecated)
        '192.168.0.0' => 16,     // private
        '198.18.0.0' => 15,      // benchmarking
        '198.51.100.0' => 24,    // documentation TEST-NET-2
        '203.0.113.0' => 24,     // documentation TEST-NET-3
        '224.0.0.0' => 4,        // multicast
        '240.0.0.0' => 4,        // reserved + broadcast
    ];

    /** @var array<string, int> */
    private const IPV6_BLOCKED = [
        '::1' => 128,            // loopback
        '::' => 128,             // unspecified
        '100::' => 64,           // discard-only
        '2001::' => 32,          // Teredo
        '2001:10::' => 28,       // ORCHID
        '2001:20::' => 28,       // ORCHIDv2
        '2001:db8::' => 32,      // documentation
        '3fff::' => 20,          // documentation (RFC 9637)
        'fc00::' => 7,           // unique local
        'fe80::' => 10,          // link-local
        'ff00::' => 8,           // multicast
    ];

    private function __construct(
        public readonly string $packed,
        public readonly int $family,
    ) {}

    /**
     * Parse a textual IPv4/IPv6 address. Returns null for hostnames and for
     * malformed input. Zone identifiers (fe80::1%eth0) are rejected.
     */
    public static function parse(string $ip): ?self
    {
        $ip = trim($ip);
        if ($ip === '' || str_contains($ip, '%')) {
            return null;
        }

        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }

        return new self($packed, strlen($packed) === 4 ? 4 : 6);
    }

    public function isIpv4(): bool
    {
        return $this->family === 4;
    }

    public function isIpv6(): bool
    {
        return $this->family === 6;
    }

    public function isPublic(): bool
    {
        return $this->blockedReason() === null;
    }

    public function isBlocked(): bool
    {
        return ! $this->isPublic();
    }

    /**
     * A stable machine-readable reason why the address is not publicly
     * routable, or null when it is.
     */
    public function blockedReason(): ?string
    {
        if ($this->isIpv4()) {
            return $this->ipv4BlockedReason();
        }

        return $this->ipv6BlockedReason();
    }

    public function normalized(): string
    {
        $text = inet_ntop($this->packed);

        return $text === false ? '' : $text;
    }

    /**
     * Extract an embedded IPv4 address when this is a mapped, compatible,
     * NAT64 or 6to4 address; null otherwise.
     */
    public function embeddedIpv4(): ?self
    {
        if (! $this->isIpv6()) {
            return null;
        }

        if ($this->inIpv6('::ffff:0:0', 96)) {
            return new self(substr($this->packed, 12, 4), 4);
        }

        if ($this->inIpv6('::', 96)) {
            return new self(substr($this->packed, 12, 4), 4);
        }

        if ($this->inIpv6('64:ff9b::', 96)) {
            return new self(substr($this->packed, 12, 4), 4);
        }

        if ($this->inIpv6('2002::', 16)) {
            return new self(substr($this->packed, 2, 4), 4);
        }

        return null;
    }

    private function ipv4BlockedReason(): ?string
    {
        foreach (self::IPV4_BLOCKED as $network => $prefix) {
            if ($this->inIpv4($network, $prefix)) {
                return match ($network) {
                    '10.0.0.0', '172.16.0.0', '192.168.0.0' => 'private',
                    '100.64.0.0' => 'cgnat',
                    '127.0.0.0' => 'loopback',
                    '169.254.0.0' => 'link_local',
                    '224.0.0.0' => 'multicast',
                    '240.0.0.0' => 'reserved',
                    '192.0.2.0', '198.51.100.0', '203.0.113.0' => 'documentation',
                    '198.18.0.0' => 'benchmarking',
                    '0.0.0.0' => 'unspecified',
                    default => 'reserved',
                };
            }
        }

        return null;
    }

    private function ipv6BlockedReason(): ?string
    {
        // IPv4-mapped ::ffff:0:0/96: allow only if the embedded IPv4 is public.
        if ($this->inIpv6('::ffff:0:0', 96)) {
            $embedded = $this->embeddedIpv4();

            return $embedded?->isPublic() === true ? null : ($embedded?->blockedReason() ?? 'mapped');
        }

        // Deprecated IPv4-compatible ::/96 (other than :: and ::1, matched above).
        if ($this->inIpv6('::', 96) && ! $this->inIpv6('::1', 128) && ! $this->inIpv6('::', 128)) {
            return 'ipv4_compatible';
        }

        // NAT64 and 6to4 translate to an IPv4 target: never acceptable here.
        if ($this->inIpv6('64:ff9b::', 96)) {
            return 'nat64';
        }

        if ($this->inIpv6('2002::', 16)) {
            return 'six_to_four';
        }

        foreach (self::IPV6_BLOCKED as $network => $prefix) {
            if ($this->inIpv6($network, $prefix)) {
                return match ($network) {
                    'fc00::' => 'private',
                    'fe80::' => 'link_local',
                    'ff00::' => 'multicast',
                    '::1' => 'loopback',
                    '::' => 'unspecified',
                    '2001:db8::', '3fff::' => 'documentation',
                    default => 'reserved',
                };
            }
        }

        // Only global unicast (2000::/3) is routable on the public internet.
        if (! $this->inIpv6('2000::', 3)) {
            return 'not_global_unicast';
        }

        return null;
    }

    private function inIpv4(string $network, int $prefix): bool
    {
        $net = @inet_pton($network);

        return $net !== false && self::cidrMatch($this->packed, $net, $prefix);
    }

    private function inIpv6(string $network, int $prefix): bool
    {
        $net = @inet_pton($network);

        return $net !== false && self::cidrMatch($this->packed, $net, $prefix);
    }

    private static function cidrMatch(string $packed, string $network, int $prefix): bool
    {
        if (strlen($packed) !== strlen($network)) {
            return false;
        }

        $wholeBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($wholeBytes > 0 && strncmp($packed, $network, $wholeBytes) !== 0) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($packed[$wholeBytes]) & $mask) === (ord($network[$wholeBytes]) & $mask);
    }
}
