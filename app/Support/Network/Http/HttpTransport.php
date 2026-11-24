<?php

declare(strict_types=1);

namespace App\Support\Network\Http;

interface HttpTransport
{
    /**
     * Perform exactly one request against the pinned IP. Implementations must
     * never follow redirects and must never re-resolve the hostname.
     *
     * @throws HttpTransportException
     */
    public function send(PinnedRequest $request): RawHttpResponse;
}
