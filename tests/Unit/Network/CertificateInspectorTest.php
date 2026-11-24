<?php

declare(strict_types=1);

namespace Tests\Unit\Network;

use App\Support\Network\Tls\CertificateInfo;
use App\Support\Network\Tls\CertificateInspector;
use PHPUnit\Framework\TestCase;

final class CertificateInspectorTest extends TestCase
{
    private const NOW = 1_800_000_000;

    public function test_parses_short_and_long_distinguished_name_keys(): void
    {
        $short = (new CertificateInspector)->fromParsed([
            'subject' => ['CN' => 'example.com'],
            'issuer' => ['O' => 'Example CA', 'CN' => 'Example Intermediate'],
            'name' => '/CN=example.com',
            'validFrom_time_t' => self::NOW - 100,
            'validTo_time_t' => self::NOW + 86_400 * 90,
            'extensions' => ['subjectAltName' => 'DNS:example.com, DNS:*.example.com, IP Address:93.184.216.34'],
            'serialNumberHex' => 'ABCD',
        ], self::NOW);

        $this->assertSame('example.com', $short->commonName);
        $this->assertSame('Example CA', $short->issuerOrganization);
        $this->assertSame(90, $short->daysLeft);
        $this->assertSame(['example.com', '*.example.com'], $short->dnsNames);
        $this->assertSame(['93.184.216.34'], $short->ipAddresses);
        $this->assertTrue($short->matchesHost('www.example.com'));
        $this->assertTrue($short->matchesHost('example.com'));
        $this->assertFalse($short->matchesHost('deep.www.example.com'));
        $this->assertTrue($short->matchesHost('93.184.216.34'));
        $this->assertFalse($short->matchesHost('93.184.216.35'));

        $long = (new CertificateInspector)->fromParsed([
            'subject' => ['commonName' => 'example.org', 'organizationName' => 'Example Org'],
            'issuer' => ['organizationName' => 'Long CA', 'commonName' => 'Long Intermediate'],
            'name' => 'CN=example.org',
            'validFrom_time_t' => self::NOW - 100,
            'validTo_time_t' => self::NOW + 86_400 * 10,
        ], self::NOW);

        $this->assertSame('example.org', $long->commonName);
        $this->assertSame('Long CA', $long->issuerOrganization);
        $this->assertTrue($long->matchesHost('example.org'));
    }

    public function test_falls_back_to_the_cn_when_no_san_is_present(): void
    {
        $certificate = (new CertificateInspector)->fromParsed([
            'subject' => ['CN' => 'legacy.example'],
            'issuer' => ['CN' => 'Legacy CA'],
            'name' => '/CN=legacy.example',
            'validFrom_time_t' => self::NOW,
            'validTo_time_t' => self::NOW + 86_400,
        ], self::NOW);

        $this->assertSame([], $certificate->dnsNames);
        $this->assertTrue($certificate->matchesHost('legacy.example'));
        $this->assertFalse($certificate->matchesHost('other.example'));
    }

    public function test_expiry_and_self_signed_flags(): void
    {
        $expired = (new CertificateInspector)->fromParsed([
            'subject' => ['CN' => 'old.example'],
            'issuer' => ['CN' => 'old.example'],
            'name' => '/CN=old.example',
            'validFrom_time_t' => self::NOW - 86_400 * 100,
            'validTo_time_t' => self::NOW - 86_400 * 2,
        ], self::NOW);

        $this->assertSame(-2, $expired->daysLeft);
        $this->assertTrue($expired->isExpired());
        $this->assertTrue($expired->selfSigned);
    }

    public function test_wildcard_does_not_match_the_bare_suffix_or_multiple_labels(): void
    {
        $certificate = new CertificateInfo(
            commonName: '*.example.com',
            subject: 'CN=*.example.com',
            issuer: 'Test CA',
            issuerOrganization: 'Test',
            validFrom: self::NOW - 10,
            validTo: self::NOW + 10,
            daysLeft: 1,
            dnsNames: ['*.example.com'],
            ipAddresses: [],
            selfSigned: false,
            serial: null,
        );

        $this->assertTrue($certificate->matchesHost('api.example.com'));
        $this->assertFalse($certificate->matchesHost('example.com'));
        $this->assertFalse($certificate->matchesHost('a.b.example.com'));
        $this->assertFalse($certificate->matchesHost('api.example.org'));
    }
}
