<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Network\Tls\CertificateInfo;
use App\Support\Network\Tls\TlsInspector;
use App\Support\Network\Tls\TlsReport;
use App\Support\Network\ValidatedUrl;

final class FakeTlsInspector implements TlsInspector
{
    private ?TlsReport $report = null;

    /** @var list<ValidatedUrl> */
    public array $calls = [];

    public function report(TlsReport $report): self
    {
        $this->report = $report;

        return $this;
    }

    public function inspect(ValidatedUrl $target, int $timeoutSeconds): TlsReport
    {
        $this->calls[] = $target;

        return $this->report ?? TlsReport::unreachable('tls_handshake_failed');
    }

    public static function validReport(string $host = 'example.com', int $daysLeft = 120, int $chainCount = 3): TlsReport
    {
        return new TlsReport(
            connected: true,
            verified: true,
            error: null,
            protocol: 'TLSv1.3',
            cipherName: 'TLS_AES_256_GCM_SHA384',
            cipherBits: 256,
            certificate: new CertificateInfo(
                commonName: $host,
                subject: 'CN='.$host,
                issuer: 'CN=Test Intermediate CA, O=Fedaa Test',
                issuerOrganization: 'Fedaa Test',
                validFrom: time() - 86_400 * 30,
                validTo: time() + 86_400 * $daysLeft,
                daysLeft: $daysLeft,
                dnsNames: [$host, 'www.'.$host],
                ipAddresses: [],
                selfSigned: false,
                serial: '01ABCDEF',
            ),
            chainCount: $chainCount,
            handshakeMs: 120,
        );
    }
}
