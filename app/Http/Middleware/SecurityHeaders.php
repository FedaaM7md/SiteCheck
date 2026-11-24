<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response hardening for the application itself.
 *
 * The app ships no inline scripts and no inline event handlers, so the CSP
 * keeps `script-src 'self'`. `style-src` allows inline styles only because the
 * score ring sets a single CSS custom property (`--score: <int>`) on an element.
 * HSTS is emitted only when the request is secure or explicitly enabled, since
 * the local demo runs over plain HTTP.
 *
 * Copyright (c) 2026 Fedaa Mohamed. All rights reserved.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        if ($request->isSecure() || config('sitecheck.hsts')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $script = "'self'";
        $connect = "'self'";

        // When the Vite dev server is running, allow its origin so HMR works.
        $devOrigin = $this->viteDevOrigin();
        if ($devOrigin !== null) {
            $script .= ' '.$devOrigin;
            $connect .= ' '.$devOrigin.' '.str_replace(['http://', 'https://'], ['ws://', 'wss://'], $devOrigin);
        }

        return implode('; ', [
            "default-src 'self'",
            "script-src {$script}",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src {$connect}",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "manifest-src 'self'",
        ]);
    }

    private function viteDevOrigin(): ?string
    {
        $hot = public_path('hot');

        if (! is_file($hot)) {
            return null;
        }

        $url = trim((string) @file_get_contents($hot));

        if (preg_match('#^(https?://[^\s/]+)#', $url, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
