<?php

declare(strict_types=1);

namespace App\Support\Network\Dns;

/**
 * Discovers the operating system's recursive resolvers so the raw CAA query
 * can be sent to the same DNS infrastructure as every other lookup. On
 * Windows the list comes from `ipconfig /all`; on Unix from resolv.conf.
 * An explicit SITECHECK_DNS_RESOLVERS environment value wins.
 */
final class ResolverDiscovery
{
    /** @var list<string>|null */
    private ?array $cached = null;

    /** @return list<string> */
    public function addresses(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $fromEnv = (string) config('sitecheck.dns_resolvers', '');
        if ($fromEnv !== '') {
            return $this->cached = $this->filter(array_map('trim', explode(',', $fromEnv)));
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return $this->cached = $this->fromWindows();
        }

        $content = @file_get_contents('/etc/resolv.conf');
        if (is_string($content) && preg_match_all('/^\s*nameserver\s+([0-9a-fA-F:.]+)/m', $content, $matches) > 0) {
            return $this->cached = $this->filter($matches[1]);
        }

        return $this->cached = [];
    }

    /** @return list<string> */
    public static function parseWindowsOutput(string $output): array
    {
        $addresses = [];
        $collecting = false;

        foreach (preg_split('/\r?\n/', $output) ?: [] as $line) {
            if (preg_match('/DNS Servers?\s*[.:]+\s*([0-9a-fA-F:.]+%?\d*)/i', $line, $match) === 1) {
                $addresses[] = $match[1];
                $collecting = true;

                continue;
            }

            if ($collecting && preg_match('/^\s+([0-9a-fA-F:.]+%?\d*)\s*$/', $line, $match) === 1) {
                $addresses[] = $match[1];

                continue;
            }

            if (trim($line) !== '') {
                $collecting = false;
            }
        }

        return $addresses;
    }

    /** @return list<string> */
    private function fromWindows(): array
    {
        if (! function_exists('shell_exec')) {
            return [];
        }

        $output = @shell_exec('ipconfig /all 2>NUL');
        if (! is_string($output) || $output === '') {
            return [];
        }

        return $this->filter(self::parseWindowsOutput($output));
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function filter(array $values): array
    {
        $clean = [];

        foreach ($values as $value) {
            $value = trim($value);
            // Drop zone ids (fe80::1%8) that cannot be used in a UDP URL.
            if ($value === '' || str_contains($value, '%') || in_array($value, ['0.0.0.0', '::'], true)) {
                continue;
            }

            if (filter_var($value, FILTER_VALIDATE_IP) !== false) {
                $clean[$value] = true;
            }
        }

        return array_slice(array_keys($clean), 0, 3);
    }
}
