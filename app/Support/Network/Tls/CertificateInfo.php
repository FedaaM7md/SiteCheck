<?php

declare(strict_types=1);

namespace App\Support\Network\Tls;

final class CertificateInfo
{
    /**
     * @param  list<string>  $dnsNames
     * @param  list<string>  $ipAddresses
     */
    public function __construct(
        public readonly ?string $commonName,
        public readonly string $subject,
        public readonly string $issuer,
        public readonly ?string $issuerOrganization,
        public readonly int $validFrom,
        public readonly int $validTo,
        public readonly int $daysLeft,
        public readonly array $dnsNames,
        public readonly array $ipAddresses,
        public readonly bool $selfSigned,
        public readonly ?string $serial,
    ) {}

    public function matchesHost(string $host): bool
    {
        $host = strtolower(rtrim($host, '.'));

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $needle = @inet_pton($host);
            foreach ($this->ipAddresses as $ip) {
                if ($needle !== false && @inet_pton($ip) === $needle) {
                    return true;
                }
            }

            return false;
        }

        if ($this->dnsNames !== []) {
            foreach ($this->dnsNames as $name) {
                if ($this->matchesDnsName($host, $name)) {
                    return true;
                }
            }

            return false;
        }

        return $this->commonName !== null && $this->matchesDnsName($host, $this->commonName);
    }

    public function isExpired(): bool
    {
        return $this->daysLeft < 0;
    }

    private function matchesDnsName(string $host, string $name): bool
    {
        $name = strtolower(rtrim($name, '.'));

        if ($name === $host) {
            return true;
        }

        if (! str_starts_with($name, '*.')) {
            return false;
        }

        $suffix = substr($name, 2);
        if ($suffix === '' || ! str_ends_with($host, '.'.$suffix)) {
            return false;
        }

        $prefix = substr($host, 0, strlen($host) - strlen($suffix) - 1);

        return $prefix !== '' && ! str_contains($prefix, '.');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'common_name' => $this->commonName,
            'subject' => $this->subject,
            'issuer' => $this->issuer,
            'issuer_organization' => $this->issuerOrganization,
            'valid_from' => $this->validFrom,
            'valid_to' => $this->validTo,
            'days_left' => $this->daysLeft,
            'dns_names' => $this->dnsNames,
            'ip_addresses' => $this->ipAddresses,
            'self_signed' => $this->selfSigned,
            'serial' => $this->serial,
        ];
    }
}
