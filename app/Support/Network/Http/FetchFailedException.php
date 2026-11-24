<?php

declare(strict_types=1);

namespace App\Support\Network\Http;

use RuntimeException;

final class FetchFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $detail = '',
    ) {
        parent::__construct($detail !== '' ? $detail : $reason);
    }
}
