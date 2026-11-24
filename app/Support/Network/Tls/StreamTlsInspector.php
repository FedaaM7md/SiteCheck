<?php

declare(strict_types=1);

namespace App\Support\Network\Tls;

use App\Support\Network\ValidatedUrl;

/**
 * TLS inspection through a pinned socket. Certificate verification is
 * attempted with full verification. Verification is only disabled when the
 * failure is specifically certificate-related, purely to read the certificate
 * so the report can explain the problem. Network failures are retried on the
 * other already-validated addresses instead of being misreported as bad
 * certificates. No application data is sent.
 */
final class StreamTlsInspector implements TlsInspector
{
    public function __construct(
        private readonly CertificateInspector $certificateInspector = new CertificateInspector,
    ) {}

    public function inspect(ValidatedUrl $target, int $timeoutSeconds): TlsReport
    {
        $startedAt = microtime(true);
        $deadline = $startedAt + max(3, $timeoutSeconds);

        $candidates = array_slice(array_values(array_unique(array_merge(
            [$target->ip],
            $target->resolvedIps,
        ))), 0, 2);

        $lastError = 'tls_handshake_failed';

        foreach ($candidates as $ip) {
            $remaining = $deadline - microtime(true);
            if ($remaining < 1.5) {
                break;
            }

            [$report, $error] = $this->attempt($this->withIp($target, $ip), max(2, (int) floor($remaining)), $startedAt);
            if ($report !== null) {
                return $report;
            }

            $lastError = $error !== '' ? $error : $lastError;
        }

        return TlsReport::unreachable($lastError);
    }

    /**
     * @return array{0: ?TlsReport, 1: string}
     */
    private function attempt(ValidatedUrl $target, int $timeout, float $startedAt): array
    {
        [$socket, $error] = $this->handshake($target, $timeout, true);

        if ($socket !== null) {
            return [$this->report($socket, true, null, $startedAt), ''];
        }

        if (! $this->looksLikeCertificateError($error)) {
            return [null, $error];
        }

        // Read the certificate even though it is not trusted, so the report
        // can explain exactly what is wrong.
        [$fallbackSocket, $fallbackError] = $this->handshake($target, $timeout, false);
        if ($fallbackSocket === null) {
            return [null, $error !== '' ? $error : $fallbackError];
        }

        return [$this->report($fallbackSocket, false, $error, $startedAt), ''];
    }

    private function report($socket, bool $verified, ?string $error, float $startedAt): TlsReport
    {
        $crypto = stream_get_meta_data($socket)['crypto'] ?? [];
        $params = stream_context_get_params($socket);
        $sslOptions = $params['options']['ssl'] ?? [];
        $peerCertificate = $sslOptions['peer_certificate'] ?? null;
        $chain = is_array($sslOptions['peer_certificate_chain'] ?? null) ? $sslOptions['peer_certificate_chain'] : [];

        $certificate = null;
        if ($peerCertificate !== null) {
            $parsed = @openssl_x509_parse($peerCertificate, false);
            if (is_array($parsed)) {
                $certificate = $this->certificateInspector->fromParsed($parsed);
            }
        }

        fclose($socket);

        return new TlsReport(
            connected: true,
            verified: $verified,
            error: $verified ? null : ($error !== null && $error !== '' ? $this->cleanError($error) : 'certificate_verification_failed'),
            protocol: isset($crypto['protocol']) ? (string) $crypto['protocol'] : null,
            cipherName: isset($crypto['cipher_name']) ? (string) $crypto['cipher_name'] : null,
            cipherBits: isset($crypto['cipher_bits']) ? (int) $crypto['cipher_bits'] : null,
            certificate: $certificate,
            chainCount: count($chain),
            handshakeMs: (int) round((microtime(true) - $startedAt) * 1000),
        );
    }

    /** @return array{0: resource|null, 1: string} */
    private function handshake(ValidatedUrl $target, int $timeoutSeconds, bool $verify): array
    {
        $host = str_contains($target->ip, ':') ? '['.$target->ip.']' : $target->ip;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => $verify,
                'verify_peer_name' => $verify,
                'allow_self_signed' => ! $verify,
                'peer_name' => $target->host,
                'SNI_enabled' => true,
                'SNI_server_name' => $target->host,
                'capture_peer_cert' => true,
                'capture_peer_cert_chain' => true,
                'disable_compression' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
            ],
        ]);

        $errno = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            'tls://'.$host.':'.$target->port,
            $errno,
            $errstr,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if ($socket === false) {
            return [null, $this->cleanError($errstr)];
        }

        return [$socket, ''];
    }

    private function looksLikeCertificateError(string $error): bool
    {
        if ($error === '') {
            return false;
        }

        foreach (['certificate', 'verify', 'self-signed', 'self signed', 'issuer', 'hostname', 'cn='] as $needle) {
            if (stripos($error, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function withIp(ValidatedUrl $target, string $ip): ValidatedUrl
    {
        if ($target->ip === $ip) {
            return $target;
        }

        return new ValidatedUrl(
            scheme: $target->scheme,
            host: $target->host,
            port: $target->port,
            ip: $ip,
            path: $target->path,
            url: $target->url,
            resolvedIps: $target->resolvedIps,
        );
    }

    private function cleanError(string $error): string
    {
        $error = trim($error);
        if ($error === '') {
            return '';
        }

        // Drop trailing source locations sometimes appended by OpenSSL.
        $error = preg_replace('/\s*\([^)]*\)\s*$/', '', $error) ?? $error;

        return mb_substr($error, 0, 180);
    }
}
