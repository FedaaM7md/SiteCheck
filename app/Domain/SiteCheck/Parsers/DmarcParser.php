<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Parsers;

final class DmarcParser
{
    /** @param list<string> $txtRecords */
    public function parse(array $txtRecords): DmarcResult
    {
        $dmarcRecords = [];
        $otherRecords = [];

        foreach ($txtRecords as $txt) {
            $txt = trim((string) $txt);
            if ($txt === '') {
                continue;
            }

            if (preg_match('/^v=DMARC1\s*(;|$)/i', $txt) === 1) {
                $dmarcRecords[] = $txt;
            } else {
                $otherRecords[] = $txt;
            }
        }

        if ($dmarcRecords === []) {
            $result = DmarcResult::missing();

            return $otherRecords === []
                ? $result
                : new DmarcResult(false, false, null, null, [], 100, [], ['invalid_record']);
        }

        $issues = [];
        if (count($dmarcRecords) > 1) {
            $issues[] = 'multiple_records';
        }

        $tags = [];
        foreach (explode(';', $dmarcRecords[0]) as $pair) {
            $pair = trim($pair);
            if ($pair === '' || ! str_contains($pair, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $pair, 2);
            $key = strtolower(trim($key));
            $value = trim($value);

            if ($key === '' || $key === 'v') {
                continue;
            }

            if (isset($tags[$key])) {
                $issues[] = 'duplicate_tag';
            }

            $tags[$key] = $value;
        }

        $policy = isset($tags['p']) ? strtolower($tags['p']) : null;
        $subdomainPolicy = isset($tags['sp']) ? strtolower($tags['sp']) : null;

        if ($policy === null) {
            $issues[] = 'missing_policy';
        } elseif (! in_array($policy, ['none', 'quarantine', 'reject'], true)) {
            $issues[] = 'invalid_policy';
        } elseif ($policy === 'none') {
            $issues[] = 'policy_none';
        }

        $rua = [];
        if (isset($tags['rua'])) {
            foreach (explode(',', $tags['rua']) as $uri) {
                $uri = trim($uri);
                if ($uri !== '') {
                    $rua[] = $uri;
                }
            }
        }

        if ($rua === []) {
            $issues[] = 'no_rua';
        }

        $pct = 100;
        if (isset($tags['pct'])) {
            if (preg_match('/^\d{1,3}$/', $tags['pct']) === 1) {
                $pct = (int) $tags['pct'];
                if ($pct < 100) {
                    $issues[] = 'pct_less_than_100';
                }
                if ($pct > 100) {
                    $issues[] = 'invalid_pct';
                }
            } else {
                $issues[] = 'invalid_pct';
            }
        }

        $valid = $policy !== null
            && in_array($policy, ['none', 'quarantine', 'reject'], true)
            && ! in_array('multiple_records', $issues, true)
            && ! in_array('invalid_pct', $issues, true);

        return new DmarcResult(
            present: true,
            valid: $valid,
            policy: $policy,
            subdomainPolicy: $subdomainPolicy,
            rua: $rua,
            pct: $pct,
            tags: $tags,
            issues: array_values(array_unique($issues)),
        );
    }
}
