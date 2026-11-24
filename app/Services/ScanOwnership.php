<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Services;

use App\Models\Scan;
use Illuminate\Session\Store;

/**
 * History rows are private to the browser session that created them. The
 * token is random and stored server-side in the session, so it survives
 * session id regeneration and never leaks into URLs.
 */
final class ScanOwnership
{
    private const SESSION_KEY = 'sitecheck.owner_token';

    public function __construct(private readonly Store $session) {}

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (! is_string($token) || strlen($token) !== 32 || ! ctype_xdigit($token)) {
            $token = bin2hex(random_bytes(16));
            $this->session->put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function owns(Scan $scan): bool
    {
        return hash_equals($scan->owner_token, $this->token());
    }
}
