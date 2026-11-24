<?php

declare(strict_types=1);

namespace App\Support\Network;

use RuntimeException;

final class HostResolutionException extends RuntimeException
{
    public static function forHost(string $host): self
    {
        return new self(sprintf('The host "%s" could not be resolved.', $host));
    }
}
