<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Checks;

use App\Domain\SiteCheck\Dto\Finding;
use App\Domain\SiteCheck\Dto\SectionResult;
use App\Support\Network\Tls\TlsInspector;
use App\Support\Network\Tls\TlsReport;
use App\Support\Network\ValidatedUrl;

final class SslCheck
{
    public function __construct(
        private readonly TlsInspector $inspector,
        private readonly int $timeoutSeconds = 8,
    ) {}

    public function run(ValidatedUrl $target): SectionResult
    {
        if (! $target->isHttps()) {
            return new SectionResult('ssl', [
                Finding::fail('ssl_https', 'ssl', 6),
            ], ['tls' => null]);
        }

        $report = $this->inspector->inspect($target, $this->timeoutSeconds);

        return new SectionResult('ssl', $this->findings($report, $target->host), [
            'tls' => $report->toArray(),
        ], $report->connected ? null : 'tls_unreachable');
    }

    /** @return list<Finding> */
    private function findings(TlsReport $report, string $host): array
    {
        if (! $report->connected) {
            return [Finding::fail('ssl_unreachable', 'ssl', 8)];
        }

        $findings = [Finding::pass('ssl_https', 'ssl', 2)];
        $certificate = $report->certificate;

        if ($report->verified) {
            $findings[] = Finding::pass('ssl_verified', 'ssl', 5);
        } else {
            $findings[] = Finding::fail('ssl_verified', 'ssl', 5, ['reason' => $this->shortReason($report->error)]);
        }

        if ($certificate === null) {
            $findings[] = Finding::fail('ssl_certificate', 'ssl', 5);

            return $findings;
        }

        if ($certificate->matchesHost($host)) {
            $findings[] = Finding::pass('ssl_hostname', 'ssl', 4, ['host' => $host]);
        } else {
            $findings[] = Finding::fail('ssl_hostname', 'ssl', 4, ['host' => $host]);
        }

        $days = $certificate->daysLeft;
        if ($days < 0) {
            $findings[] = Finding::fail('ssl_expired', 'ssl', 4, ['days' => abs($days)]);
        } elseif ($days <= 14) {
            $findings[] = Finding::fail('ssl_expiry_soon', 'ssl', 4, ['days' => $days]);
        } elseif ($days <= 30) {
            $findings[] = Finding::warn('ssl_expiry', 'ssl', 4, ['days' => $days]);
        } else {
            $findings[] = Finding::pass('ssl_expiry', 'ssl', 4, ['days' => $days]);
        }

        $findings[] = $report->verified
            ? Finding::pass('ssl_issuer', 'ssl', 2, ['issuer' => $this->shortIssuer($certificate->issuerOrganization)])
            : Finding::warn('ssl_issuer', 'ssl', 2, ['issuer' => $this->shortIssuer($certificate->issuerOrganization)]);

        $findings[] = $report->chainCount >= 2
            ? Finding::pass('ssl_chain', 'ssl', 2, ['count' => $report->chainCount])
            : Finding::warn('ssl_chain', 'ssl', 2, ['count' => $report->chainCount]);

        $protocol = strtoupper((string) $report->protocol);
        if (str_contains($protocol, '1.3') || str_contains($protocol, '1.2')) {
            $findings[] = Finding::pass('ssl_protocol', 'ssl', 2, ['protocol' => $protocol]);
        } elseif ($protocol === '') {
            $findings[] = Finding::warn('ssl_protocol', 'ssl', 2, ['protocol' => '']);
        } elseif (str_contains($protocol, 'SSL')) {
            $findings[] = Finding::fail('ssl_protocol', 'ssl', 2, ['protocol' => $protocol]);
        } else {
            $findings[] = Finding::warn('ssl_protocol', 'ssl', 2, ['protocol' => $protocol]);
        }

        $bits = (int) ($report->cipherBits ?? 0);
        $cipher = (string) ($report->cipherName ?? '');
        $weakCipher = preg_match('/RC4|3DES|DES|MD5|NULL|EXPORT/i', $cipher) === 1;
        if ($bits >= 128 && ! $weakCipher) {
            $findings[] = Finding::pass('ssl_cipher', 'ssl', 1, ['cipher' => $cipher, 'bits' => $bits]);
        } else {
            $findings[] = Finding::warn('ssl_cipher', 'ssl', 1, ['cipher' => $cipher, 'bits' => $bits]);
        }

        $findings[] = Finding::info('ssl_san', 'ssl', ['count' => count($certificate->dnsNames)]);

        return $findings;
    }

    private function shortReason(?string $reason): string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? 'verification_failed' : mb_substr($reason, 0, 120);
    }

    private function shortIssuer(?string $issuer): string
    {
        $issuer = trim((string) $issuer);

        return $issuer === '' ? 'unknown' : mb_substr($issuer, 0, 60);
    }
}
