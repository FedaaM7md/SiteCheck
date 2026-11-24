# Security model — SiteCheck

> **This is a local demo, not a production service.** There are no accounts, no
> multi-tenancy and no hardened deployment. Read this document before exposing
> the app to a network. The release 1.1 review and its fixes are in
> [docs/SECURITY-REVIEW.md](docs/SECURITY-REVIEW.md).

## 1. What the app does with the network

A scan makes outbound requests to an untrusted hostname supplied by the visitor.
Because the app runs inside a private network, the highest-risk failure would be
using it as a proxy into that network. The SSRF design therefore treats every
target as hostile.

### 1.1 SSRF gate (`App\Support\Network\UrlGuard`)

Before any socket is opened — including every redirect hop and every
`robots.txt` / `sitemap.xml` probe — the URL is validated:

- Only absolute `http` and `https` URLs are accepted.
- Embedded credentials (`user:pass@`) are rejected.
- Only ports **80** and **443** are allowed.
- Control characters, backslashes and over-long URLs are rejected.
- Hostnames are lower-cased, IDN-converted to punycode, and stripped of trailing dots.
- Single-label hostnames, `localhost`, `*.local`, `*.internal`, `*.localdomain`,
  `*.home.arpa`, `*.lan`, `*.corp`, `*.intranet`, `*.test`, `*.invalid`,
  `*.example`, `*.onion` and `*.arpa` are rejected.
- Decimal/hex/octal IPv4 encodings (`2130706433`, `0x7f.0.0.1`, `0177.0.0.1`)
  are rejected before resolution.
- The hostname is resolved **locally** (PHP's resolver). Every returned A and
  AAAA address must be public. If **any** address is blocked, the whole target
  is rejected. IPv6-only targets are supported.
- Blocked IPv4: `0/8`, `10/8`, `100.64/10`, `127/8`, `169.254/16` (including
  `169.254.169.254`), `172.16/12`, `192.0.0/24`, `192.0.2/24`, `192.88.99/24`,
  `192.168/16`, `198.18/15`, `198.51.100/24`, `203.0.113/24`, `224/4`, `240/4`.
- Blocked IPv6: `::1`, `::`, `::/96` IPv4-compatible, `::ffff:0:0/96` IPv4-mapped
  (when the embedded IPv4 is blocked), `64:ff9b::/96` NAT64, `100::/64`,
  `2001::/32` Teredo, `2001:10::/28`, `2001:20::/28`, `2001:db8::/32`,
  `2002::/16` 6to4, `3fff::/20`, `fc00::/7`, `fe80::/10`, `ff00::/8`. Anything
  outside global unicast `2000::/3` is rejected.
- A single validated IP is pinned for the connection; the other validated
  addresses may be used as fallbacks **only** after they pass the same gate.
- DNS rebinding is defeated because the hostname is resolved by us and the
  socket is opened to the literal validated IP (`peer_name`/SNI keeps TLS
  correct). The transport never re-resolves.

### 1.2 HTTP transport (`StreamTransport`)

- Manual HTTP/1.1 GET against the pinned IP; no proxies, no redirects inside the
  transport.
- Redirects are followed by the fetcher (max 5) and re-validated hop by hop;
  relative `Location` values are resolved locally. Loops are rejected.
- TLS verification is always on for page requests (`verify_peer`,
  `verify_peer_name`, SNI hostname).
- Response headers are capped at 64 KiB; bodies at 512 KiB; connect/request
  timeouts are configured (defaults 5 s / 12 s) and the whole fetch has a deadline.
- Only `http,https` are requested. IPv4 and IPv6 are supported.

### 1.3 TLS inspection (`StreamTlsInspector`)

- The socket is pinned to the validated IP with `peer_name` set to the hostname.
- The first handshake uses full verification. Verification is disabled **only**
  when the failure is certificate-related, and only to read the certificate so
  the report can explain the issue. No application data is sent.
- Network failures are retried on another validated address; timeouts are never
  reported as bad certificates.

### 1.4 DNS budget and CAA

PHP's DNS functions are synchronous and expose no per-query timeout, so a
wall-clock budget bounds them. This PHP build exposes no `DNS_CAA` constant, so
CAA is queried over the DNS wire format to the operating system resolver
(discovered from `ipconfig /all` on Windows or `/etc/resolv.conf`;
`SITECHECK_DNS_RESOLVERS` overrides). If no resolver can be discovered, CAA is
reported as unavailable rather than as a missing record.

## 2. Application security

- **CSRF:** all state-changing routes are POST/DELETE behind Laravel's web
  middleware and `@csrf`.
- **Authorization:** there is no login. History is scoped by a random 128-bit
  token stored server-side in the session. Every report, print, export, delete
  and rescan action verifies ownership; unauthorized access returns 404 (no
  existence leak).
- **Content-Security-Policy:** every response carries a strict policy with
  `script-src 'self'`, `frame-ancestors 'none'`, `base-uri 'self'`,
  `form-action 'self'` and `object-src 'none'`. There are no inline scripts or
  inline event handlers, so `unsafe-inline`/`unsafe-eval` are not needed for
  scripts. `style-src` allows inline styles only for the score ring's single
  CSS custom property.
- **Other headers:** `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
  `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`,
  `Cross-Origin-Opener-Policy: same-origin`, `Cross-Origin-Resource-Policy:
  same-origin`, `X-Permitted-Cross-Domain-Policies: none`. HSTS is sent only for
  secure requests or when `SITECHECK_HSTS=true`.
- **Output escaping:** all Blade output uses `{{ }}`; raw HTML is never rendered
  from scanned content. HTML parsing uses `DOMDocument` with `LIBXML_NONET` and
  never executes scripts.
- **Validation:** the form request enforces length limits, target syntax and DKIM
  selector characters; the SSRF gate enforces the network policy server-side.
- **Rate limiting:** named `scan` limiter (per IP, per session, per day) with a
  localized 429 page. Defaults: 6/min per IP, 3/min per session, 120/day per IP.
- **Secrets:** `.env` is not committed; `.env.example` contains dummy values and
  `APP_DEBUG=false`. No API keys are used.
- **Dependencies:** `composer audit` reports no advisories; `npm audit` reports
  0 vulnerabilities.
- **Scan data:** stored in local SQLite. Reports include public DNS records, HTTP
  headers and certificate metadata; no request bodies or credentials are sent.
- **Session cookie:** Laravel defaults (HttpOnly, SameSite=Lax). `Secure` is
  disabled in the example because the local server is HTTP; enable it behind HTTPS.

## 3. Demo mode

`DEMO_MODE=true` adds a small footer note (“Demo build — sample data”), seeds
24 clearly fictional sample scans (reserved `.example` hosts) across the last
four months, and schedules an hourly `demo:reset` that clears history and
reseeds the samples. Sample rows are read-only for visitors (delete/rescan
return 404) and are marked “Sample”; they contain no real company data. It adds
no login. Setting `DEMO_MODE=false` hides the note and sample rows and disables
the schedule. There are no demo accounts.

## 4. Deliberate limitations

- **No authentication or user accounts.** Any visitor can scan and keep a private
  local history.
- **Not hardened for hostile multi-user hosting.** A scan consumes CPU/time
  (bounded but real); there is no queue isolation.
- **No DMARC aggregate report ingestion** and no recursive SPF evaluation across
  includes (the lookup budget is parsed, not executed).
- **Scans can still reach public websites.** SSRF protection prevents
  private/internal targets; it does not prevent scanning the public internet.
- **Rate limiting depends on the cache/session store**; the demo uses the
  database cache. Use a shared store for multi-node deployments.
- **CAA resolver discovery is platform-dependent.**
- **The API is a browser UI**, not a versioned public API.

## 5. Reporting a vulnerability

This is a proprietary internal demo. Report security issues to the owner (Fedaa
Mohamed) through the private project channel; do not open public issues or
publish proof-of-concept code.

## 6. Deployment checklist (if ever exposed)

1. HTTPS-only with HSTS; `SESSION_SECURE_COOKIE=true`; `SITECHECK_HSTS=true`.
2. `APP_ENV=production`, `APP_DEBUG=false`, unique `APP_KEY`, real `APP_URL`.
3. Put scans behind a queue/worker with per-user quotas.
4. Replace SQLite with a managed database and a shared cache/session store.
5. Add authentication if history becomes multi-user.
6. Add egress firewall rules that independently deny RFC1918/link-local/metadata
   ranges as defense in depth.
7. Centralize logs and alert on repeated `blocked_address` reasons.
