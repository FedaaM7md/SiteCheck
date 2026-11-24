<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Network\HostResolutionException;
use App\Support\Network\HostResolver;

final class FakeHostResolver implements HostResolver
{
    /** @var array<string, list<string>|callable> */
    private array $answers = [];

    /** @var list<string> */
    public array $resolved = [];

    /** @param list<string>|callable $ips */
    public function add(string $host, array|callable $ips): self
    {
        $this->answers[strtolower($host)] = $ips;

        return $this;
    }

    public function resolve(string $host): array
    {
        $host = strtolower(rtrim($host, '.'));
        $this->resolved[] = $host;

        if (! array_key_exists($host, $this->answers)) {
            throw HostResolutionException::forHost($host);
        }

        $answer = $this->answers[$host];
        $ips = is_callable($answer) ? $answer($host) : $answer;

        if ($ips === []) {
            throw HostResolutionException::forHost($host);
        }

        return $ips;
    }
}
