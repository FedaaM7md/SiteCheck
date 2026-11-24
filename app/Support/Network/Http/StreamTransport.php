<?php

declare(strict_types=1);

namespace App\Support\Network\Http;

/**
 * HTTP/1.1 client built on PHP streams. It connects directly to the pinned,
 * validated IP (with SNI and hostname verification for HTTPS), writes a
 * minimal GET request and parses the response with strict size and time
 * limits. Using streams keeps TLS trust aligned with the rest of PHP and
 * avoids any proxy or environment redirect.
 */
final class StreamTransport implements HttpTransport
{
    public function send(PinnedRequest $request): RawHttpResponse
    {
        $candidates = array_values(array_unique(array_merge([$request->ip], $request->fallbackIps)));
        $candidates = array_slice($candidates, 0, 3);
        $startedAt = microtime(true);
        $lastError = null;

        foreach ($candidates as $ip) {
            $remaining = $request->timeoutSeconds - (microtime(true) - $startedAt);
            if ($remaining < 1.0) {
                break;
            }

            try {
                return $this->attempt($request, $ip, max(1, (int) floor($remaining)));
            } catch (HttpTransportException $exception) {
                $lastError = $exception;
            }
        }

        throw $lastError ?? new HttpTransportException('timeout', 'No validated address could be reached.');
    }

    private function attempt(PinnedRequest $request, string $ip, int $timeout): RawHttpResponse
    {
        $startedAt = microtime(true);
        $deadline = $startedAt + $timeout;

        $scheme = strtolower((string) parse_url($request->url, PHP_URL_SCHEME));
        $secure = $scheme === 'https';
        $endpoint = (str_contains($ip, ':') ? '['.$ip.']' : $ip).':'.$request->port;

        $context = $secure
            ? stream_context_create(['ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'peer_name' => $request->host,
                'SNI_enabled' => true,
                'SNI_server_name' => $request->host,
                'disable_compression' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
            ]])
            : stream_context_create([]);

        $errno = 0;
        $errstr = '';
        $connectStartedAt = microtime(true);
        $socket = @stream_socket_client(
            ($secure ? 'tls://' : 'tcp://').$endpoint,
            $errno,
            $errstr,
            max(1, min($request->connectTimeoutSeconds, $timeout)),
            STREAM_CLIENT_CONNECT,
            $context,
        );
        $connectTimeMs = (int) round((microtime(true) - $connectStartedAt) * 1000);

        if ($socket === false) {
            throw new HttpTransportException(
                'transport_error',
                sprintf('Stream error %d: %s', $errno, trim($errstr)),
            );
        }

        try {
            $this->write($socket, $this->requestHead($request), $deadline);

            $buffer = '';
            $status = 0;
            $headers = [];

            for ($attempt = 0; $attempt < 3; $attempt++) {
                $buffer = $this->readHead($socket, $buffer, $request, $deadline);
                [$status, $headers, $buffer] = $this->parseHead($buffer);

                if ($status >= 100 && $status < 200) {
                    continue;
                }

                break;
            }

            if ($status < 100) {
                throw new HttpTransportException('no_response', 'The server did not return a valid HTTP status line.');
            }

            $body = '';
            $truncated = false;

            $transferEncoding = strtolower(implode(',', $headers['transfer-encoding'] ?? []));
            $contentLength = isset($headers['content-length'][0]) ? (int) $headers['content-length'][0] : null;

            if (str_contains($transferEncoding, 'chunked')) {
                [$body, $truncated] = $this->readChunked($socket, $buffer, $request, $deadline);
            } elseif ($contentLength !== null && $contentLength >= 0) {
                [$body, $truncated] = $this->readFixed($socket, $buffer, $contentLength, $request, $deadline);
            } else {
                [$body, $truncated] = $this->readUntilClose($socket, $buffer, $request, $deadline);
            }

            $encoding = strtolower(implode(',', $headers['content-encoding'] ?? []));
            if (! $truncated && str_contains($encoding, 'gzip')) {
                $decoded = @gzdecode($body);
                if (is_string($decoded)) {
                    $body = $decoded;
                }
            }

            return new RawHttpResponse(
                status: $status,
                headers: $headers,
                body: $body,
                truncated: $truncated,
                totalTimeMs: (int) round((microtime(true) - $startedAt) * 1000),
                connectTimeMs: $connectTimeMs,
                primaryIp: $ip,
            );
        } finally {
            fclose($socket);
        }
    }

    private function requestHead(PinnedRequest $request): string
    {
        $scheme = strtolower((string) parse_url($request->url, PHP_URL_SCHEME));
        $path = parse_url($request->url, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';
        $query = parse_url($request->url, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            $path .= '?'.$query;
        }

        $defaultPort = $scheme === 'https' ? 443 : 80;
        $hostHeader = str_contains($request->host, ':') ? '['.$request->host.']' : $request->host;
        if ($request->port !== $defaultPort) {
            $hostHeader .= ':'.$request->port;
        }

        $lines = array_merge([
            'GET '.$path.' HTTP/1.1',
            'Host: '.$hostHeader,
            'User-Agent: '.$request->userAgent,
            'Accept-Encoding: gzip',
            'Connection: close',
        ], $request->headers);

        return implode("\r\n", $lines)."\r\n\r\n";
    }

    private function readHead($socket, string $buffer, PinnedRequest $request, float $deadline): string
    {
        while (strpos($buffer, "\r\n\r\n") === false) {
            if (strlen($buffer) > $request->maxHeaderBytes) {
                throw new HttpTransportException('headers_too_large', 'The response headers exceeded the configured limit.');
            }

            $chunk = $this->pull($socket, $deadline);
            if ($chunk === '') {
                break;
            }

            $buffer .= $chunk;
        }

        if (strpos($buffer, "\r\n\r\n") === false) {
            throw new HttpTransportException('no_response', 'The server did not return complete HTTP headers.');
        }

        return $buffer;
    }

    /**
     * @return array{0: int, 1: array<string, list<string>>, 2: string}
     */
    private function parseHead(string $buffer): array
    {
        $split = strpos($buffer, "\r\n\r\n");
        $head = substr($buffer, 0, (int) $split);
        $rest = substr($buffer, (int) $split + 4);

        $lines = explode("\r\n", $head);
        $statusLine = (string) array_shift($lines);
        $status = 0;

        if (preg_match('~^HTTP/\d(?:\.\d)?\s+(\d{3})~', $statusLine, $matches) === 1) {
            $status = (int) $matches[1];
        }

        $headers = [];
        foreach ($lines as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))][] = trim($value);
        }

        return [$status, $headers, $rest];
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function readFixed($socket, string $buffer, int $length, PinnedRequest $request, float $deadline): array
    {
        $cap = min($length, $request->maxBodyBytes);
        $body = substr($buffer, 0, $cap);
        $truncated = strlen($buffer) > $cap || $length > $cap;

        while (strlen($body) < $cap) {
            $chunk = $this->pull($socket, $deadline);
            if ($chunk === '') {
                break;
            }
            $body .= $chunk;
        }

        if (strlen($body) > $cap) {
            $body = substr($body, 0, $cap);
            $truncated = true;
        }

        return [$body, $truncated];
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function readUntilClose($socket, string $buffer, PinnedRequest $request, float $deadline): array
    {
        $body = $buffer;
        $truncated = false;

        while (strlen($body) < $request->maxBodyBytes) {
            $chunk = $this->pull($socket, $deadline);
            if ($chunk === '') {
                break;
            }
            $body .= $chunk;
        }

        if (strlen($body) > $request->maxBodyBytes) {
            $body = substr($body, 0, $request->maxBodyBytes);
            $truncated = true;
        }

        return [$body, $truncated];
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function readChunked($socket, string $buffer, PinnedRequest $request, float $deadline): array
    {
        $decoded = '';
        $truncated = false;

        while (true) {
            while (strpos($buffer, "\r\n") === false) {
                $chunk = $this->pull($socket, $deadline);
                if ($chunk === '') {
                    return [substr($decoded, 0, $request->maxBodyBytes), true];
                }
                $buffer .= $chunk;

                if (strlen($buffer) > $request->maxBodyBytes + 65_536) {
                    return [substr($decoded, 0, $request->maxBodyBytes), true];
                }
            }

            $lineEnd = strpos($buffer, "\r\n");
            $sizeLine = substr($buffer, 0, (int) $lineEnd);
            $buffer = substr($buffer, (int) $lineEnd + 2);
            $size = (int) hexdec(trim(explode(';', $sizeLine)[0]));

            if ($size === 0) {
                break;
            }

            while (strlen($buffer) < $size + 2) {
                $chunk = $this->pull($socket, $deadline);
                if ($chunk === '') {
                    return [substr($decoded, 0, $request->maxBodyBytes), true];
                }
                $buffer .= $chunk;
            }

            $decoded .= substr($buffer, 0, $size);
            $buffer = substr($buffer, $size + 2);

            if (strlen($decoded) > $request->maxBodyBytes) {
                $decoded = substr($decoded, 0, $request->maxBodyBytes);
                $truncated = true;

                break;
            }
        }

        return [$decoded, $truncated];
    }

    private function pull($socket, float $deadline): string
    {
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            return '';
        }

        stream_set_timeout($socket, max(1, (int) ceil($remaining)));
        $chunk = @fread($socket, 8192);

        return is_string($chunk) ? $chunk : '';
    }

    private function write($socket, string $payload, float $deadline): void
    {
        $written = 0;
        $length = strlen($payload);

        while ($written < $length) {
            if (microtime(true) >= $deadline) {
                throw new HttpTransportException('timeout', 'Writing the request timed out.');
            }

            $result = @fwrite($socket, substr($payload, $written));
            if ($result === false || $result === 0) {
                throw new HttpTransportException('transport_error', 'The request could not be sent.');
            }

            $written += $result;
        }
    }
}
