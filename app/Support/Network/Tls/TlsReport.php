<?php

declare(strict_types=1);

namespace App\Support\Network\Tls;

final class TlsReport
{
    public function __construct(
        public readonly bool $connected,
        public readonly bool $verified,
        public readonly ?string $error,
        public readonly ?string $protocol,
        public readonly ?string $cipherName,
        public readonly ?int $cipherBits,
        public readonly ?CertificateInfo $certificate,
        public readonly int $chainCount,
        public readonly int $handshakeMs,
    ) {}

    public static function unreachable(string $reason): self
    {
        return new self(
            connected: false,
            verified: false,
            error: $reason,
            protocol: null,
            cipherName: null,
            cipherBits: null,
            certificate: null,
            chainCount: 0,
            handshakeMs: 0,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'connected' => $this->connected,
            'verified' => $this->verified,
            'error' => $this->error,
            'protocol' => $this->protocol,
            'cipher' => $this->cipherName,
            'cipher_bits' => $this->cipherBits,
            'chain_count' => $this->chainCount,
            'handshake_ms' => $this->handshakeMs,
            'certificate' => $this->certificate?->toArray(),
        ];
    }
}
