<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SiteCheck configuration
|--------------------------------------------------------------------------
|
| Copyright (c) 2026 Fedaa Mohamed. All rights reserved.
|
| Network limits are deliberately conservative: every scan is a series of
| outbound requests against untrusted hosts, so timeouts and size caps are
| part of the SSRF hardening, not only of the UX.
|
*/

return [

    'name' => 'SiteCheck',

    // Demo mode surfaces a reset banner and schedules the hourly history reset.
    'demo_mode' => (bool) env('DEMO_MODE', true),

    // HSTS is only sent over HTTPS; enable explicitly for a TLS deployment.
    'hsts' => (bool) env('SITECHECK_HSTS', false),

    'limits' => [
        'connect_timeout' => (int) env('SITECHECK_CONNECT_TIMEOUT', 5),
        'request_timeout' => (int) env('SITECHECK_REQUEST_TIMEOUT', 12),
        'tls_timeout' => (int) env('SITECHECK_TLS_TIMEOUT', 8),
        'max_body_bytes' => (int) env('SITECHECK_MAX_BODY_BYTES', 524288),
        'max_header_bytes' => (int) env('SITECHECK_MAX_HEADER_BYTES', 65536),
        'max_redirects' => (int) env('SITECHECK_MAX_REDIRECTS', 5),
        'dns_budget_seconds' => (int) env('SITECHECK_DNS_BUDGET', 8),
        'scan_total_seconds' => (int) env('SITECHECK_SCAN_TOTAL', 60),
        'max_url_length' => 2048,
    ],

    // Only plain HTTP/HTTPS on the two standard web ports may be contacted.
    'allowed_ports' => [80, 443],

    // Optional comma-separated resolvers used for the raw CAA lookup.
    'dns_resolvers' => env('SITECHECK_DNS_RESOLVERS', ''),

    'user_agent' => 'SiteCheck/2.0 (+local health checker)',

    'rate_limits' => [
        'per_ip_per_minute' => 6,
        'per_session_per_minute' => 3,
        'per_ip_per_day' => 120,
    ],

    'section_weights' => [
        'dns' => 15,
        'ssl' => 30,
        'email' => 15,
        'http' => 25,
        'seo' => 15,
    ],

    'grades' => [
        ['min' => 90, 'grade' => 'A'],
        ['min' => 80, 'grade' => 'B'],
        ['min' => 70, 'grade' => 'C'],
        ['min' => 60, 'grade' => 'D'],
        ['min' => 0, 'grade' => 'F'],
    ],

];
