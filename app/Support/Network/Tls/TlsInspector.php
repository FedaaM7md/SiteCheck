<?php

declare(strict_types=1);

namespace App\Support\Network\Tls;

use App\Support\Network\ValidatedUrl;

interface TlsInspector
{
    /**
     * Perform a TLS handshake against the pinned IP of the validated target.
     */
    public function inspect(ValidatedUrl $target, int $timeoutSeconds): TlsReport;
}
