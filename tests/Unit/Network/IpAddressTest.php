<?php

declare(strict_types=1);

namespace Tests\Unit\Network;

use App\Support\Network\IpAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IpAddressTest extends TestCase
{
    #[DataProvider('blockedAddresses')]
    public function test_blocked_ranges_are_rejected(string $ip, string $reason): void
    {
        $address = IpAddress::parse($ip);

        $this->assertNotNull($address, $ip.' should parse');
        $this->assertFalse($address->isPublic(), $ip.' must not be public');
        $this->assertSame($reason, $address->blockedReason(), $ip.' reason');
    }

    /** @return array<string, array{string, string}> */
    public static function blockedAddresses(): array
    {
        return [
            'unspecified v4' => ['0.0.0.0', 'unspecified'],
            'private 10/8' => ['10.1.2.3', 'private'],
            'private 172.16/12' => ['172.16.5.5', 'private'],
            'private 172.31' => ['172.31.255.254', 'private'],
            'private 192.168/16' => ['192.168.1.1', 'private'],
            'loopback 127/8' => ['127.0.0.1', 'loopback'],
            'loopback high' => ['127.255.255.254', 'loopback'],
            'metadata 169.254.169.254' => ['169.254.169.254', 'link_local'],
            'link local neighbour' => ['169.254.1.1', 'link_local'],
            'cgnat' => ['100.64.0.1', 'cgnat'],
            'cgnat upper' => ['100.127.255.254', 'cgnat'],
            'multicast' => ['224.0.0.1', 'multicast'],
            'reserved 240/4' => ['240.0.0.1', 'reserved'],
            'broadcast' => ['255.255.255.255', 'reserved'],
            'documentation 192.0.2' => ['192.0.2.10', 'documentation'],
            'benchmarking' => ['198.18.0.1', 'benchmarking'],
            'documentation 198.51.100' => ['198.51.100.7', 'documentation'],
            'documentation 203.0.113' => ['203.0.113.7', 'documentation'],
            '6to4 relay' => ['192.88.99.1', 'reserved'],
            'loopback v6' => ['::1', 'loopback'],
            'unspecified v6' => ['::', 'unspecified'],
            'unique local fc00' => ['fc00::1', 'private'],
            'unique local fd' => ['fd12:3456:789a::1', 'private'],
            'link local v6' => ['fe80::1', 'link_local'],
            'multicast v6' => ['ff02::1', 'multicast'],
            'documentation v6' => ['2001:db8::1', 'documentation'],
            'teredo' => ['2001::1', 'reserved'],
            'discard only' => ['100::1', 'reserved'],
            'documentation 3fff' => ['3fff::1', 'documentation'],
            'mapped loopback' => ['::ffff:127.0.0.1', 'loopback'],
            'mapped private' => ['::ffff:10.0.0.1', 'private'],
            'nat64 private' => ['64:ff9b::7f00:1', 'nat64'],
            'six to four private' => ['2002:7f00:1::1', 'six_to_four'],
            'ipv4 compatible' => ['::127.0.0.1', 'ipv4_compatible'],
        ];
    }

    #[DataProvider('publicAddresses')]
    public function test_public_addresses_are_allowed(string $ip): void
    {
        $address = IpAddress::parse($ip);

        $this->assertNotNull($address);
        $this->assertTrue($address->isPublic(), $ip.' should be public');
        $this->assertNull($address->blockedReason());
    }

    /** @return array<string, array{string}> */
    public static function publicAddresses(): array
    {
        return [
            'google dns' => ['8.8.8.8'],
            'cloudflare dns' => ['1.1.1.1'],
            'iana' => ['93.184.216.34'],
            'just below private 172.16' => ['172.15.255.255'],
            'just above private 172.16' => ['172.32.0.1'],
            'above 10/8' => ['11.0.0.1'],
            'cloudflare v6' => ['2606:4700:4700::1111'],
            'google v6' => ['2001:4860:4860::8888'],
            'mapped public' => ['::ffff:8.8.8.8'],
        ];
    }

    public function test_invalid_addresses_return_null(): void
    {
        $this->assertNull(IpAddress::parse(''));
        $this->assertNull(IpAddress::parse('not-an-ip'));
        $this->assertNull(IpAddress::parse('127.0.0.1%eth0'));
        $this->assertNull(IpAddress::parse('999.1.1.1'));
    }

    public function test_normalisation(): void
    {
        $this->assertSame('8.8.8.8', IpAddress::parse('8.8.8.8')?->normalized());
        $this->assertSame('2001:db8::1', IpAddress::parse('2001:0db8:0000:0000:0000:0000:0000:0001')?->normalized());
    }
}
