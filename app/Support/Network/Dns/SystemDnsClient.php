<?php

declare(strict_types=1);

namespace App\Support\Network\Dns;

/**
 * Thin wrapper over dns_get_record with a wall-clock budget. PHP's DNS
 * functions are synchronous and expose no per-query timeout, so the client
 * stops issuing queries (and reports "timeout") once the budget is spent.
 */
final class SystemDnsClient implements DnsClient
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $cache = [];

    private string $status = 'ok';

    private float $startedAt;

    /** @var array<string, int> */
    private const TYPE_MAP = [
        'A' => DNS_A,
        'AAAA' => DNS_AAAA,
        'CNAME' => DNS_CNAME,
        'NS' => DNS_NS,
        'MX' => DNS_MX,
        'TXT' => DNS_TXT,
        'SOA' => DNS_SOA,
    ];

    public function __construct(
        private readonly int $budgetSeconds = 8,
        private readonly ?CaaResolver $caaResolver = null,
    ) {
        $this->startedAt = microtime(true);
    }

    public function records(string $host, string $type): array
    {
        $type = strtoupper($type);
        $key = strtolower($host).'|'.$type;

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        if ($type === 'CAA') {
            return $this->cache[$key] = $this->caaRecords($host);
        }

        if (microtime(true) - $this->startedAt > $this->budgetSeconds) {
            $this->status = 'timeout';

            return $this->cache[$key] = [];
        }

        if (! isset(self::TYPE_MAP[$type])) {
            return $this->cache[$key] = [];
        }

        $raw = @dns_get_record(rtrim($host, '.'), self::TYPE_MAP[$type]);
        if ($type === 'A' && (! is_array($raw) || $raw === [])) {
            $fallback = @gethostbynamel(rtrim($host, '.'));
            if (is_array($fallback)) {
                $raw = array_map(static fn (string $ip): array => ['type' => 'A', 'ip' => $ip], $fallback);
            }
        }

        if ($raw === false) {
            $this->status = 'error';

            return $this->cache[$key] = [];
        }

        if (microtime(true) - $this->startedAt > $this->budgetSeconds) {
            $this->status = 'timeout';
        }

        return $this->cache[$key] = array_values(array_map(
            fn (array $record): array => $this->normalize($type, $record),
            array_filter($raw, static fn (array $record): bool => ($record['type'] ?? null) === $type),
        ));
    }

    public function status(): string
    {
        return $this->status;
    }

    public function canResolveCaa(): bool
    {
        return $this->caaResolver?->available() ?? false;
    }

    /** @return list<array<string, mixed>> */
    private function caaRecords(string $host): array
    {
        if ($this->caaResolver === null) {
            return [];
        }

        $records = $this->caaResolver->lookup($host);
        if ($records === null) {
            $this->status = 'partial';

            return [];
        }

        return array_values(array_map(
            static fn (array $record): array => [
                'flags' => (int) $record['flags'],
                'tag' => (string) $record['tag'],
                'value' => (string) $record['value'],
            ],
            $records,
        ));
    }

    /** @param array<string, mixed> $record */
    private function normalize(string $type, array $record): array
    {
        return match ($type) {
            'A' => ['ip' => (string) ($record['ip'] ?? '')],
            'AAAA' => ['ip' => (string) ($record['ipv6'] ?? '')],
            'CNAME' => ['target' => $this->target((string) ($record['target'] ?? ''))],
            'NS' => ['target' => $this->target((string) ($record['target'] ?? ''))],
            'MX' => [
                'target' => $this->target((string) ($record['target'] ?? '')),
                'priority' => (int) ($record['pri'] ?? 0),
            ],
            'TXT' => ['text' => $this->txt($record)],
            'CAA' => [
                'flags' => (int) ($record['flags'] ?? 0),
                'tag' => strtolower((string) ($record['tag'] ?? '')),
                'value' => (string) ($record['value'] ?? ''),
            ],
            'SOA' => [
                'mname' => $this->target((string) ($record['mname'] ?? '')),
                'rname' => $this->target((string) ($record['rname'] ?? '')),
                'serial' => (int) ($record['serial'] ?? 0),
            ],
            default => $record,
        };
    }

    private function target(string $value): string
    {
        return strtolower(rtrim(trim($value), '.'));
    }

    /** @param array<string, mixed> $record */
    private function txt(array $record): string
    {
        if (isset($record['txt']) && is_string($record['txt'])) {
            return $record['txt'];
        }

        if (isset($record['entries']) && is_array($record['entries'])) {
            return implode('', array_map(static fn ($entry): string => (string) $entry, $record['entries']));
        }

        return '';
    }
}
