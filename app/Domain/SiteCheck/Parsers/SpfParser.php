<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Parsers;

/**
 * Parses SPF TXT records. It is intentionally conservative: it never performs
 * recursive lookups itself (the DNS check reports the raw records), it only
 * analyses the syntax and the documented DNS-lookup budget.
 */
final class SpfParser
{
    /** @var list<string> */
    private const LOOKUP_MECHANISMS = ['include', 'a', 'mx', 'ptr', 'exists'];

    /** @var list<string> */
    private const KNOWN_MECHANISMS = [
        'all', 'include', 'a', 'mx', 'ptr', 'ip4', 'ip6', 'exists', 'redirect', 'exp',
    ];

    /** @param list<string> $txtRecords */
    public function parse(array $txtRecords): SpfResult
    {
        $records = [];
        foreach ($txtRecords as $txt) {
            $txt = trim((string) $txt);
            if (preg_match('/^v=spf1(\s|$)/i', $txt) === 1) {
                $records[] = $txt;
            }
        }

        if ($records === []) {
            return SpfResult::missing();
        }

        $issues = [];
        if (count($records) > 1) {
            $issues[] = 'multiple_records';
        }

        $record = $records[0];
        $terms = preg_split('/\s+/', trim($record)) ?: [];
        array_shift($terms); // drop "v=spf1"

        $lookups = 0;
        $allQualifier = null;
        $redirect = null;
        $mechanisms = [];

        foreach ($terms as $term) {
            $term = trim($term);
            if ($term === '') {
                continue;
            }

            if (str_contains($term, '=')) {
                [$name, $value] = array_pad(explode('=', $term, 2), 2, '');
                $name = strtolower($name);

                if ($name === 'redirect') {
                    $lookups++;
                    $redirect = $value;
                    $mechanisms['redirect'] = $value;

                    continue;
                }

                if ($name === 'exp') {
                    continue;
                }

                $issues[] = 'unknown_modifier';

                continue;
            }

            $qualifier = '+';
            if (in_array($term[0], ['+', '-', '~', '?'], true)) {
                $qualifier = $term[0];
                $term = substr($term, 1);
            }

            $name = strtolower(explode(':', $term, 2)[0]);
            $name = explode('/', $name, 2)[0];

            if (! in_array($name, self::KNOWN_MECHANISMS, true)) {
                $issues[] = 'unknown_mechanism';

                continue;
            }

            if (in_array($name, self::LOOKUP_MECHANISMS, true)) {
                $lookups++;
            }

            if ($name === 'all') {
                $allQualifier = $qualifier;
            }

            if ($name === 'ptr') {
                $issues[] = 'ptr_mechanism';
            }

            $mechanisms[$name] = $qualifier;
        }

        if ($allQualifier === null) {
            $issues[] = 'missing_all';
        } elseif ($allQualifier === '+') {
            $issues[] = 'plus_all';
        } elseif ($allQualifier === '?') {
            $issues[] = 'neutral_all';
        } elseif ($allQualifier === '~') {
            $issues[] = 'softfail_all';
        }

        if ($lookups > 10) {
            $issues[] = 'too_many_lookups';
        }

        if ($redirect !== null && $allQualifier !== null) {
            $issues[] = 'redirect_ignored';
        }

        return new SpfResult(
            present: true,
            records: $records,
            valid: count($records) === 1 && ! in_array('unknown_mechanism', $issues, true),
            allQualifier: $allQualifier,
            lookupCount: $lookups,
            hasRedirect: $redirect !== null,
            redirectTarget: $redirect,
            mechanisms: $mechanisms,
            issues: array_values(array_unique($issues)),
        );
    }
}
