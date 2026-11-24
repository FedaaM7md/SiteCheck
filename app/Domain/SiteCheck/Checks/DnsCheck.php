<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Checks;

use App\Domain\SiteCheck\Dto\Finding;
use App\Domain\SiteCheck\Dto\SectionResult;
use App\Support\Network\Dns\DnsClient;

final class DnsCheck
{
    public function __construct(private readonly DnsClient $dns) {}

    public function run(string $host): SectionResult
    {
        $a = $this->dns->records($host, 'A');
        $aaaa = $this->dns->records($host, 'AAAA');
        $cname = $this->dns->records($host, 'CNAME');
        $ns = $this->dns->records($host, 'NS');
        $mx = $this->dns->records($host, 'MX');
        $txt = $this->dns->records($host, 'TXT');
        $caa = $this->dns->records($host, 'CAA');
        $soa = $this->dns->records($host, 'SOA');

        $findings = [];
        $hosts = array_merge(
            array_column($a, 'ip'),
            array_column($aaaa, 'ip'),
        );

        if ($hosts === []) {
            $findings[] = Finding::fail('dns_a', 'dns', 4);
        } else {
            $findings[] = Finding::pass('dns_a', 'dns', 4, ['count' => count($hosts)]);
        }

        $findings[] = count($aaaa) > 0
            ? Finding::pass('dns_aaaa', 'dns', 0, ['count' => count($aaaa)])
            : Finding::info('dns_aaaa_missing', 'dns');

        if ($cname !== []) {
            $findings[] = Finding::info('dns_cname', 'dns', [
                'target' => (string) ($cname[0]['target'] ?? ''),
            ]);
        }

        $nsCount = count($ns);
        if ($nsCount >= 2) {
            $findings[] = Finding::pass('dns_ns', 'dns', 2, ['count' => $nsCount]);
        } elseif ($nsCount === 1) {
            $findings[] = Finding::warn('dns_ns', 'dns', 2, ['count' => 1]);
        } else {
            $findings[] = Finding::fail('dns_ns', 'dns', 2);
        }

        $findings[] = Finding::info('dns_mx', 'dns', ['count' => count($mx)]);

        $txtCount = count($txt);
        $findings[] = Finding::info('dns_txt', 'dns', ['count' => $txtCount]);

        if ($caa !== []) {
            $findings[] = Finding::pass('dns_caa', 'dns', 2, ['count' => count($caa)]);
        } elseif (! $this->dns->canResolveCaa() || in_array($this->dns->status(), ['partial', 'timeout', 'error'], true)) {
            $findings[] = Finding::info('dns_caa_unavailable', 'dns');
        } else {
            $findings[] = Finding::warn('dns_caa', 'dns', 2);
        }

        if ($soa !== []) {
            $findings[] = Finding::pass('dns_soa', 'dns', 1);
        } else {
            $findings[] = Finding::warn('dns_soa', 'dns', 1);
        }

        $findings[] = Finding::info('dns_resolver_status', 'dns', ['status' => $this->dns->status()]);

        $details = [
            'a' => $a,
            'aaaa' => $aaaa,
            'cname' => $cname,
            'ns' => $ns,
            'mx' => $mx,
            'txt' => $txt,
            'caa' => $caa,
            'soa' => $soa,
            'resolver_status' => $this->dns->status(),
        ];

        return new SectionResult('dns', $findings, $details);
    }
}
