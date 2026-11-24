<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Network\Dns\DnsClient;

final class FakeDnsClient implements DnsClient
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $records = [];

    /** @var list<string> */
    public array $queries = [];

    private bool $caaSupported = true;

    private string $status = 'ok';

    /** @param list<array<string, mixed>> $records */
    public function set(string $host, string $type, array $records): self
    {
        $this->records[strtolower($host).'|'.strtoupper($type)] = $records;

        return $this;
    }

    public function canResolveCaa(): bool
    {
        return $this->caaSupported;
    }

    public function withoutCaa(): self
    {
        $this->caaSupported = false;

        return $this;
    }

    public function withStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function records(string $host, string $type): array
    {
        $this->queries[] = strtolower($host).'|'.strtoupper($type);

        return $this->records[strtolower($host).'|'.strtoupper($type)] ?? [];
    }

    public function status(): string
    {
        return $this->status;
    }

    /** @return list<array{text: string}> */
    public static function txt(string ...$values): array
    {
        return array_map(static fn (string $value): array => ['text' => $value], $values);
    }

    /** @return list<array{ip: string}> */
    public static function addresses(string ...$ips): array
    {
        return array_map(static fn (string $ip): array => ['ip' => $ip], $ips);
    }
}
