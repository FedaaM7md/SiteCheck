<?php

declare(strict_types=1);

namespace App\Support\Network;

use RuntimeException;

/**
 * Raised whenever a URL cannot be safely contacted. The reason is a stable
 * short code so the UI can show a translated explanation without leaking
 * internal network details.
 */
final class UnsafeUrlException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $detail = '',
    ) {
        parent::__construct($detail !== '' ? $detail : $reason);
    }

    public static function because(string $reason, string $detail = ''): self
    {
        return new self($reason, $detail);
    }
}
