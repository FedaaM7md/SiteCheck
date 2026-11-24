<?php

declare(strict_types=1);

namespace App\Support\Network\Tls;

final class CertificateInspector
{
    /** @param array<string, mixed> $parsed Result of openssl_x509_parse() */
    public function fromParsed(array $parsed, ?int $now = null): CertificateInfo
    {
        $now ??= time();
        $validFrom = (int) ($parsed['validFrom_time_t'] ?? 0);
        $validTo = (int) ($parsed['validTo_time_t'] ?? 0);
        $subject = (string) ($parsed['name'] ?? '');
        $issuer = is_array($parsed['issuer'] ?? null) ? $parsed['issuer'] : [];
        $subjectParts = is_array($parsed['subject'] ?? null) ? $parsed['subject'] : [];
        $issuerString = $this->formatDistinguishedName($issuer);
        $commonName = $this->firstValue($subjectParts, 'CN') ?? $this->firstValue($subjectParts, 'commonName');
        if ($commonName === null && preg_match('/(?:^|,)\s*(?:CN|commonName)=([^,]+)/i', $subject, $matches) === 1) {
            $commonName = trim($matches[1]);
        }
        $issuerOrganization = $this->firstValue($issuer, 'O') ?? $this->firstValue($issuer, 'organizationName');
        $dnsNames = [];
        $ipAddresses = [];

        $san = (string) ($parsed['extensions']['subjectAltName'] ?? '');
        if ($san !== '') {
            foreach (explode(',', $san) as $entry) {
                $entry = trim($entry);
                if (str_starts_with($entry, 'DNS:')) {
                    $dnsNames[] = strtolower(rtrim(substr($entry, 4), '.'));
                } elseif (str_starts_with($entry, 'IP Address:')) {
                    $ipAddresses[] = trim(substr($entry, 11));
                }
            }
        }

        return new CertificateInfo(
            commonName: $commonName,
            subject: $subject !== '' ? $subject : $this->formatDistinguishedName($subjectParts),
            issuer: $issuerString,
            issuerOrganization: $this->firstValue($issuer, 'O') ?? $this->firstValue($issuer, 'organizationName'),
            validFrom: $validFrom,
            validTo: $validTo,
            daysLeft: (int) floor(($validTo - $now) / 86400),
            dnsNames: array_values(array_unique($dnsNames)),
            ipAddresses: array_values(array_unique($ipAddresses)),
            selfSigned: $subjectParts === $issuer,
            serial: isset($parsed['serialNumberHex']) ? (string) $parsed['serialNumberHex'] : (isset($parsed['serialNumber']) ? (string) $parsed['serialNumber'] : null),
        );
    }

    /**
     * @param  array<string, mixed>  $name
     */
    private function formatDistinguishedName(array $name): string
    {
        $parts = [];
        foreach ($name as $key => $values) {
            if (! is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                $parts[] = $key.'='.$value;
            }
        }

        return implode(', ', $parts);
    }

    /** @param array<string, mixed> $name */
    private function firstValue(array $name, string $key): ?string
    {
        $values = $name[$key] ?? null;
        if (is_array($values)) {
            $values = $values[0] ?? null;
        }

        return is_string($values) && $values !== '' ? $values : null;
    }
}
