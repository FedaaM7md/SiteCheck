<?php

declare(strict_types=1);

namespace App\Support\Network\Dns;

/**
 * CAA is not exposed by dns_get_record() on every PHP build (notably on
 * Windows), so this tiny client sends a CAA query to the system resolver
 * directly. It is only used for type CAA; every other record type goes
 * through PHP's own DNS API.
 */
final class CaaResolver
{
    public function __construct(
        private readonly ResolverDiscovery $discovery = new ResolverDiscovery,
        private readonly float $timeoutSeconds = 2.0,
    ) {}

    public function available(): bool
    {
        return $this->discovery->addresses() !== [];
    }

    /**
     * @return list<array{flags: int, tag: string, value: string}>|null
     *                                                                  null when the resolver could not be reached at all
     */
    public function lookup(string $host): ?array
    {
        $addresses = $this->discovery->addresses();
        if ($addresses === []) {
            return null;
        }

        $id = random_int(1, 65535);
        $query = DnsPacket::encodeQuery($id, $host);

        foreach ($addresses as $address) {
            $records = $this->exchange($address, $query, $id);
            if ($records !== null) {
                return $records;
            }
        }

        return null;
    }

    /** @return list<array{flags: int, tag: string, value: string}>|null */
    private function exchange(string $address, string $query, int $id): ?array
    {
        $response = $this->udp($address, $query);

        if ($response === null) {
            $response = $this->tcp($address, $query);
        }

        if ($response === null) {
            return null;
        }

        $parsed = DnsPacket::parseResponse($response);
        if ($parsed === null || $parsed['id'] !== $id) {
            return null;
        }

        if ($parsed['truncated']) {
            $tcpResponse = $this->tcp($address, $query);
            if ($tcpResponse !== null) {
                $tcpParsed = DnsPacket::parseResponse($tcpResponse);
                if ($tcpParsed !== null && $tcpParsed['id'] === $id) {
                    $parsed = $tcpParsed;
                }
            }
        }

        // NOERROR or NXDOMAIN both mean "no usable CAA record".
        if (! in_array($parsed['rcode'], [0, 3], true)) {
            return null;
        }

        return $parsed['records'];
    }

    private function udp(string $address, string $query): ?string
    {
        $endpoint = 'udp://'.$this->endpoint($address);

        $socket = @stream_socket_client($endpoint, $errno, $errstr, $this->timeoutSeconds);
        if ($socket === false) {
            return null;
        }

        stream_set_timeout($socket, (int) ceil($this->timeoutSeconds));
        @fwrite($socket, $query);
        $response = @fread($socket, 4096);
        fclose($socket);

        return is_string($response) && strlen($response) >= 12 ? $response : null;
    }

    private function tcp(string $address, string $query): ?string
    {
        $endpoint = 'tcp://'.$this->endpoint($address);

        $socket = @stream_socket_client($endpoint, $errno, $errstr, $this->timeoutSeconds);
        if ($socket === false) {
            return null;
        }

        stream_set_timeout($socket, (int) ceil($this->timeoutSeconds));

        $written = @fwrite($socket, pack('n', strlen($query)).$query);
        if ($written === false) {
            fclose($socket);

            return null;
        }

        $prefix = @fread($socket, 2);
        if (! is_string($prefix) || strlen($prefix) !== 2) {
            fclose($socket);

            return null;
        }

        $length = unpack('nlength', $prefix)['length'] ?? 0;
        if ($length < 12 || $length > 65535) {
            fclose($socket);

            return null;
        }

        $response = '';
        while (strlen($response) < $length) {
            $chunk = @fread($socket, $length - strlen($response));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $response .= $chunk;
        }

        fclose($socket);

        return strlen($response) >= 12 ? $response : null;
    }

    private function endpoint(string $address): string
    {
        return str_contains($address, ':') ? '['.$address.']:53' : $address.':53';
    }
}
