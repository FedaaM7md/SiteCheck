<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Checks;

use App\Domain\SiteCheck\Dto\Finding;
use App\Domain\SiteCheck\Dto\SectionResult;
use App\Domain\SiteCheck\Parsers\DmarcParser;
use App\Domain\SiteCheck\Parsers\SpfParser;
use App\Support\Network\Dns\DnsClient;

final class EmailCheck
{
    /** @var list<string> */
    private const DEFAULT_SELECTORS = ['default', 'google', 'selector1', 'selector2', 'k1', 'mail', 'dkim', 's1'];

    public function __construct(
        private readonly DnsClient $dns,
        private readonly SpfParser $spfParser = new SpfParser,
        private readonly DmarcParser $dmarcParser = new DmarcParser,
    ) {}

    /** @param list<string> $selectors */
    public function run(string $host, array $selectors = []): SectionResult
    {
        $spf = $this->spfParser->parse(array_map(
            static fn (array $record): string => (string) ($record['text'] ?? ''),
            $this->dns->records($host, 'TXT'),
        ));
        $dmarc = $this->dmarcParser->parse(array_map(
            static fn (array $record): string => (string) ($record['text'] ?? ''),
            $this->dns->records('_dmarc.'.$host, 'TXT'),
        ));
        $mx = $this->dns->records($host, 'MX');
        $dkim = $this->inspectDkim($host, $this->sanitizeSelectors($selectors));

        $details = [
            'spf' => [
                'present' => $spf->present,
                'valid' => $spf->valid,
                'records' => $spf->records,
                'all' => $spf->allQualifier,
                'lookups' => $spf->lookupCount,
                'redirect' => $spf->redirectTarget,
                'issues' => $spf->issues,
            ],
            'dmarc' => [
                'present' => $dmarc->present,
                'valid' => $dmarc->valid,
                'policy' => $dmarc->policy,
                'subdomain_policy' => $dmarc->subdomainPolicy,
                'rua' => $dmarc->rua,
                'pct' => $dmarc->pct,
                'tags' => $dmarc->tags,
                'issues' => $dmarc->issues,
            ],
            'dkim' => $dkim,
            'mx' => $mx,
        ];

        // A null MX ("0 .") is an explicit declaration that the domain does not
        // receive mail. That is a valid configuration, so it must not be
        // punished by the SPF/DMARC checks.
        if ($mx !== [] && $this->hasNullMx($mx)) {
            return new SectionResult('email', [
                Finding::pass('email_mx_null', 'email', 3),
                Finding::info('email_mail_disabled', 'email'),
            ], $details);
        }

        $findings = [];

        // MX ------------------------------------------------------------------
        if ($mx === []) {
            $findings[] = Finding::fail('email_mx', 'email', 3);
        } else {
            $findings[] = Finding::pass('email_mx', 'email', 3, ['count' => count($mx)]);
            $findings[] = count($mx) >= 2
                ? Finding::pass('email_mx_redundancy', 'email', 1, ['count' => count($mx)])
                : Finding::warn('email_mx_redundancy', 'email', 1, ['count' => 1]);
        }

        // SPF -----------------------------------------------------------------
        if (! $spf->present) {
            $findings[] = Finding::fail('email_spf_present', 'email', 3);
        } else {
            $findings[] = Finding::pass('email_spf_present', 'email', 3);

            if (count($spf->records) > 1) {
                $findings[] = Finding::fail('email_spf_multiple', 'email', 3, ['count' => count($spf->records)]);
            }

            $all = $spf->allQualifier;
            if ($all === '+') {
                $findings[] = Finding::fail('email_spf_all_plus', 'email', 3);
            } elseif ($all === '?') {
                $findings[] = Finding::warn('email_spf_all_neutral', 'email', 3);
            } elseif ($all === '~') {
                $findings[] = Finding::warn('email_spf_all_softfail', 'email', 3);
            } elseif ($all === '-') {
                $findings[] = Finding::pass('email_spf_all', 'email', 3);
            } else {
                $findings[] = Finding::warn('email_spf_all_missing', 'email', 3);
            }

            if ($spf->lookupCount > 10) {
                $findings[] = Finding::fail('email_spf_lookups', 'email', 2, ['count' => $spf->lookupCount]);
            } else {
                $findings[] = Finding::pass('email_spf_lookups', 'email', 2, ['count' => $spf->lookupCount]);
            }

            if ($spf->hasIssue('unknown_mechanism') || $spf->hasIssue('ptr_mechanism')) {
                $findings[] = Finding::warn('email_spf_syntax', 'email', 1);
            } else {
                $findings[] = Finding::pass('email_spf_syntax', 'email', 1);
            }
        }

        // DMARC ---------------------------------------------------------------
        if (! $dmarc->present) {
            $findings[] = Finding::fail('email_dmarc_present', 'email', 4);
        } else {
            $findings[] = Finding::pass('email_dmarc_present', 'email', 4);

            if ($dmarc->policy === 'reject' || $dmarc->policy === 'quarantine') {
                $findings[] = Finding::pass('email_dmarc_policy', 'email', 3, ['policy' => (string) $dmarc->policy]);
            } elseif ($dmarc->policy === 'none') {
                $findings[] = Finding::warn('email_dmarc_policy_none', 'email', 3, ['policy' => 'none']);
            } else {
                $findings[] = Finding::fail('email_dmarc_policy_missing', 'email', 3);
            }

            if ($dmarc->rua !== []) {
                $findings[] = Finding::pass('email_dmarc_rua', 'email', 1, ['count' => count($dmarc->rua)]);
            } else {
                $findings[] = Finding::warn('email_dmarc_rua', 'email', 1);
            }

            if ($dmarc->pct < 100) {
                $findings[] = Finding::warn('email_dmarc_pct', 'email', 1, ['pct' => $dmarc->pct]);
            }
        }

        // DKIM ----------------------------------------------------------------
        if ($dkim['selectors'] === []) {
            $findings[] = Finding::info('email_dkim', 'email');
        } elseif ($dkim['working'] > 0) {
            $findings[] = Finding::pass('email_dkim', 'email', 3, [
                'working' => $dkim['working'],
                'total' => count($dkim['selectors']),
            ], ['selectors' => $dkim['selectors']]);
        } else {
            $findings[] = Finding::warn('email_dkim', 'email', 3, [
                'working' => 0,
                'total' => count($dkim['selectors']),
            ], ['selectors' => $dkim['selectors']]);
        }

        return new SectionResult('email', $findings, $details);
    }

    /**
     * @param  list<string>  $selectors
     * @return array{selectors: list<array{selector: string, found: bool, revoked: bool, key_bits: ?int, valid: bool}>, working: int}
     */
    private function inspectDkim(string $host, array $selectors): array
    {
        $results = [];
        $working = 0;

        foreach ($selectors as $selector) {
            $records = $this->dns->records($selector.'._domainkey.'.$host, 'TXT');
            $found = false;
            $revoked = false;
            $valid = false;
            $keyBits = null;

            foreach ($records as $record) {
                $txt = trim((string) ($record['text'] ?? ''));
                if ($txt === '') {
                    continue;
                }
                $tags = $this->parseDkimTags($txt);
                if (! isset($tags['p'])) {
                    continue;
                }

                $found = true;
                $key = trim($tags['p']);

                if ($key === '') {
                    $revoked = true;

                    continue;
                }

                $valid = true;
                $keyBits = $this->estimateRsaBits($key);
            }

            if ($valid) {
                $working++;
            }

            $results[] = [
                'selector' => $selector,
                'found' => $found,
                'revoked' => $revoked,
                'key_bits' => $keyBits,
                'valid' => $valid,
            ];
        }

        return ['selectors' => $results, 'working' => $working];
    }

    /** @return array<string, string> */
    private function parseDkimTags(string $txt): array
    {
        $tags = [];
        foreach (explode(';', $txt) as $pair) {
            $pair = trim($pair);
            if (! str_contains($pair, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $pair, 2);
            $tags[strtolower(trim($key))] = trim($value);
        }

        return $tags;
    }

    private function estimateRsaBits(string $base64): ?int
    {
        $der = base64_decode(preg_replace('/\s+/', '', $base64) ?? '', true);
        if ($der === false || $der === '') {
            return null;
        }

        // A very rough estimate; the key length is not exposed by DNS.
        return match (true) {
            strlen($der) >= 500 => 4096,
            strlen($der) >= 250 => 2048,
            strlen($der) >= 120 => 1024,
            default => null,
        };
    }

    /** @param list<array<string, mixed>> $mx */
    private function hasNullMx(array $mx): bool
    {
        foreach ($mx as $record) {
            if (($record['target'] ?? '') === '' && (int) ($record['priority'] ?? 0) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $selectors
     * @return list<string>
     */
    private function sanitizeSelectors(array $selectors): array
    {
        if ($selectors === []) {
            $selectors = self::DEFAULT_SELECTORS;
        }

        $clean = [];
        foreach ($selectors as $selector) {
            $selector = strtolower(trim((string) $selector));
            $selector = rtrim($selector, '.');
            if ($selector !== '' && preg_match('/^[a-z0-9]([a-z0-9-]{0,62})$/', $selector) === 1) {
                $clean[$selector] = true;
            }
        }

        return array_slice(array_keys($clean), 0, 5);
    }
}
