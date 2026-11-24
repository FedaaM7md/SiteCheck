# Security review — SiteCheck (release 1.1)

Date: 2026-11-24. Scope: the local Laravel application in this project folder.
This is a demo, not a production service. The full threat model is in
[SECURITY.md](../SECURITY.md); this document records what release 1.1 reviewed and
changed.

## 1. Threat model (summary)

| Asset | Threat | Impact | Control |
| --- | --- | --- | --- |
| The host network | SSRF via a scanned URL or redirect | Gateway into private/internal services | Resolve-and-pin guard, block lists, per-hop re-validation |
| Scan history | Cross-session disclosure (IDOR) | Another visitor reads your scans | Random session owner token checked on every action |
| The browser | Script injection via scanned content | XSS | Output escaping, no raw HTML, strict CSP, no inline scripts |
| The service | Abuse / resource exhaustion | Slow or unavailable scans | Rate limits, timeouts, body/header caps |
| Credentials | Secret leakage | n/a (no accounts, no API keys) | `.env` untracked, dummy `.env.example` |

## 2. What was checked and changed

### 2.1 Content-Security-Policy (fixed)

The app now sends a CSP on every response:

```
default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';
img-src 'self' data:; font-src 'self'; connect-src 'self';
frame-ancestors 'none'; base-uri 'self'; form-action 'self';
object-src 'none'; manifest-src 'self'
```

- To make `script-src 'self'` real, **Alpine.js was removed**. Alpine evaluates
  expressions with `new Function`, which requires `unsafe-eval`. The few
  behaviours (mobile menu, confirm dialogs, scan busy state, print button) are
  now a 1 KB vanilla module driven by `data-*` attributes in
  `resources/js/app.js`.
- All inline event handlers (`onsubmit`, `onclick`, `javascript:`) were removed
  and replaced with `data-confirm` / `data-print` handlers. A regression test
  asserts that rendered pages contain no inline handlers.
- `style-src` keeps `'unsafe-inline'` only because the score ring sets one CSS
  custom property (`--score: <int>`) on an element. No user-controlled CSS is
  written.
- When the Vite dev server is running (a `public/hot` file exists), its origin
  is added to `script-src`/`connect-src` so HMR works; production builds use
  same-origin assets only.

### 2.2 Other response headers (added)

`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
`Referrer-Policy: strict-origin-when-cross-origin`,
`Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()`,
`Cross-Origin-Opener-Policy: same-origin`,
`Cross-Origin-Resource-Policy: same-origin`,
`X-Permitted-Cross-Domain-Policies: none`. HSTS is emitted **only** for secure
requests or when `SITECHECK_HSTS=true`, because the local demo runs over HTTP.

### 2.3 SSRF (kept and unchanged)

The release 1.0 guard remains the core control: local resolution of every hostname,
rejection if **any** A/AAAA answer is non-public, connection pinned to a
validated IP, every redirect hop re-validated, ports 80/443 only, credentials
and obfuscated IPs rejected, size/time caps. It is covered by the existing
network-mocked tests. This review did not weaken it.

### 2.4 Authorization (kept)

No accounts exist. History is scoped by a random 128-bit session token and
every show/print/export/rescan/delete action checks ownership; foreign or
missing rows return 404 (no existence leak). Cross-session access is tested.

### 2.5 Input / output

- `ScanRequest` validates length, URL syntax and DKIM selector characters; the
  SSRF gate enforces the network policy server-side.
- Blade output is escaped (`{{ }}`); no `{!! !!}` with user data. `DOMDocument`
  parsing uses `LIBXML_NONET`.
- JSON export is built from stored data with `JSON_UNESCAPED_UNICODE` and an
  attachment disposition; it sets `nosniff`.

### 2.6 CSRF and rate limiting (kept)

All state-changing routes are POST/DELETE behind the web middleware with
`@csrf`. The `scan` limiter applies per IP, per session and per day, with a
localized 429 page.

### 2.7 Secrets and dependencies

- `.env` is untracked; `.env.example` has dummy values and `APP_DEBUG=false`.
- `composer audit`: no advisories. `npm audit`: 0 vulnerabilities.
- Direct dependencies are MIT / BSD-3-Clause / Apache-2.0, plus OFL fonts.

### 2.8 Demo mode

`DEMO_MODE=true` adds a quiet footer note, seeds 24 fictional sample scans
(reserved `.example` hosts, no real company data) and schedules an hourly
`demo:reset` that clears history and reseeds them. Sample rows are read-only
(delete/rescan return 404). It does not add a login. When `DEMO_MODE=false` the
note, the sample rows and the schedule are disabled. There is no demo route to
hide.

## 3. Findings fixed in this pass

| # | Finding | Fix |
| --- | --- | --- |
| 1 | No Content-Security-Policy | Added strict CSP (script-src self) |
| 2 | Alpine required `unsafe-eval` | Removed Alpine; vanilla data-attribute JS |
| 3 | Inline `onsubmit`/`onclick` handlers | Replaced with delegated handlers |
| 4 | Missing COOP/CORP/Permitted-Cross-Domain headers | Added |
| 5 | Brand/ownership ambiguity in metadata | Proprietary LICENSE, COPYRIGHT.md, headers |

## 4. Remaining risks (honest)

- **No authentication.** History is session-scoped; session-cookie theft exposes
  that session's scans. Multi-user hosting needs real auth.
- **Synchronous scans.** A slow target occupies a PHP worker; rate limits bound
  abuse but there is no queue.
- **No CSP nonce/hash.** Not needed because there are no inline scripts; if one
  is added later, use a nonce via `Vite::useCspNonce()`.
- **CAA resolver discovery is platform-dependent** (Windows `ipconfig`, Unix
  `resolv.conf`, env override).
- **Public scanning remains possible by design.** The guard prevents internal
  targets, not scanning the public internet.
- **Visual/screen-reader QA** is manual; screenshots are captured but no
  automated accessibility audit runs.

## 5. Verification

- `php artisan test` → 199 passed.
- `npm run build` → success.
- `vendor\bin\pint --test` → pass.
- `composer audit` → no advisories; `npm audit` → 0 vulnerabilities.
