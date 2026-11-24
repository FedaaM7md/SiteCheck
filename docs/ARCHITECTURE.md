# Architecture — Fedaa SiteCheck

## 1. Goal and constraints

A single-tenant, local Laravel application that turns one domain into one bilingual report. The hard constraint is safety: the server must never be usable as a gateway to its own private network. A second constraint is honesty: every check result must be derived from a real observation, never from demo data.

## 2. High-level flow

```
Browser
  │  POST /scan (CSRF, validation, throttle:scan)
  ▼
ScanController
  │  ScanInput(target, dkim selectors)
  ▼
ScanService ──────────────────────────────────────────────┐
  │ 1. UrlNormalizer (scheme, host, IDN, length)          │
  │ 2. UrlGuard (resolve, classify, pin)                  │
  │                                                       │
  ├─ DnsCheck     ── SystemDnsClient ── dns_get_record    │
  │                                  └─ CaaResolver (UDP) │
  ├─ SslCheck     ── StreamTlsInspector (pinned TLS)      │
  ├─ EmailCheck   ── DnsClient (SPF/DMARC/DKIM/MX)        │
  ├─ HttpCheck    ── SafeHttpFetcher ── StreamTransport   │
  └─ SeoCheck     ── SafeHttpFetcher + SeoParser          │
  │                                                       │
  ▼                                                       │
ScoreCalculator (section + weighted overall)              │
  ▼                                                       │
ScanReport DTO ──► Scan model (SQLite JSON) ──► redirect  │
                                                          ▼
                                       ReportPresenter → Blade
```

Failures are contained per section: an exception inside one check becomes a `check_failed` finding and the rest of the report is still produced. Only an invalid/blocked target aborts the whole scan (and is reported on the form).

## 3. Layers

### 3.1 HTTP/UI layer

- `routes/web.php`: seven web routes (home, history, scan, locale, report, print, export, rescan, delete).
- `Http/Requests/ScanRequest`: length/syntax validation, DKIM selector parsing and character rules.
- `Http/Middleware/SetLocale`: session locale → `app()->setLocale()` + Carbon locale; ar/en only.
- `Http/Controllers`: thin orchestration; ownership checks on every report action.
- `Http/Presenters/ReportPresenter`: turns a stored raw report into localized view models; also builds the localized JSON export.
- Blade views: layout, home, report, print, history, error pages, shared components (logo, icon, score ring, tone badge, language switch).

### 3.2 Domain layer

`App\Domain\SiteCheck`:

| Component | Responsibility |
| --- | --- |
| `ScanService` | Normalizes input, validates the target, runs each check, assembles `ScanReport`, bounds failures |
| `Checks\DnsCheck` | A/AAAA/CNAME/NS/MX/TXT/CAA/SOA findings and raw evidence |
| `Checks\SslCheck` | TLS connection findings and certificate evidence |
| `Checks\EmailCheck` | SPF, DMARC, DKIM and MX findings |
| `Checks\HttpCheck` | Primary fetch, plain-HTTP upgrade probe, headers/cookies/time findings |
| `Checks\SeoCheck` | Local HTML analysis plus robots.txt/sitemap probes |
| `Parsers\SpfParser`, `DmarcParser` | Pure syntax/policy parsers |
| `Parsers\HeaderAnalyzer` | Normalizes security headers and cookies |
| `Parsers\SeoParser` | `DOMDocument` parsing with `LIBXML_NONET` |
| `Scoring\ScoreCalculator` | Weighted section/overall scores, grades and tones |
| `Dto\Finding`, `SectionResult`, `ScanReport` | Immutable result model with translation-aware serialization |

### 3.3 Network layer

`App\Support\Network` is framework-independent and unit-tested:

- `IpAddress`: binary parsing and the only copy of the block lists.
- `UrlNormalizer`, `UrlGuard`, `ValidatedUrl`, `UnsafeUrlException`.
- `HostResolver` interface + `SystemHostResolver` (`dns_get_record`, `gethostbynamel` fallback).
- `Http\SafeHttpFetcher`: manual redirect loop, every hop re-validated, size/time budget.
- `Http\StreamTransport`: pinned HTTP/1.1 client with TLS verification, chunked/gzip decoding and caps.
- `Http\RedirectResolver`: RFC 3986 merge rules for `Location`.
- `Dns\SystemDnsClient`, `Dns\CaaResolver`, `Dns\DnsPacket`, `Dns\ResolverDiscovery`.
- `Tls\StreamTlsInspector`, `Tls\CertificateInspector`, `Tls\TlsReport`, `Tls\CertificateInfo`.

### 3.4 Persistence

One table, `scans`:

| Column | Purpose |
| --- | --- |
| `owner_token` (32, indexed) | Random per-session ownership token; never shown |
| `input`, `host`, `url` | Target as entered and canonical target |
| `overall_score`, `grade` | Denormalized for fast history listing |
| `section_scores` (JSON) | Five section scores |
| `report` (JSON) | Full raw report (findings, params, evidence) |
| `locale` | Locale at scan time (metadata only) |
| `duration_ms`, `scanned_at` | Timing |
| timestamps | Laravel conventions |

Reports are localized at render time, so a scan can be viewed in either language after the fact.

## 4. Request lifecycle for a scan

1. `throttle:scan` runs first (per IP, per session, per day).
2. `ScanRequest` validates the target string and DKIM selectors.
3. `ScanService` normalizes to an absolute URL and calls `UrlGuard::validate`.
4. `UrlGuard` resolves all A/AAAA records; any non-public address rejects the target.
5. The validated IP is pinned; `ValidatedUrl` carries all validated addresses for safe fallback.
6. DNS, TLS, email and HTTP checks execute. HTTP redirects are followed by `SafeHttpFetcher` (max 5) with a fresh guard call per hop.
7. SEO parses the final HTML body (already capped and truncated if needed) and probes `/robots.txt` and `/sitemap.xml` through the same fetcher.
8. `ScoreCalculator` computes section and overall scores.
9. `ScanController` persists the report and redirects to `/scans/{id}`.

## 5. Scoring

Each finding carries a severity and a positive weight; informational findings have weight 0.

```
section = round(100 * Σ(weight × factor) / Σ(weight))   factor: pass 1, warn 0.5, fail 0
overall = round(Σ(section_score × section_weight) / Σ(section_weight))
weights: dns 15, ssl 30, email 15, http 25, seo 15
grades: A ≥ 90, B ≥ 80, C ≥ 70, D ≥ 60, F otherwise
```

Weights are chosen by impact: a broken certificate or missing HTTPS affects every visitor and is weighted highest; SEO is important but not a security boundary. The weights live in `config/sitecheck.php`.

## 6. Internationalization

- `lang/ar/sitecheck.php` and `lang/en/sitecheck.php` are key-for-key mirrors (a test enforces this).
- Every finding id has a `title`, a severity-specific `detail`, and (for warn/fail) a `fix`.
- The severity-specific lookup is implemented in `Finding::translate()`: it tries `…detail.{severity}` first and falls back to `…detail`.
- Locale lives in the session; `SetLocale` runs on every web request and sets `<html lang dir>` through Blade.
- CSS uses logical properties (`ps-*`, `me-*`, `start-*`, `text-start`) so RTL mirrors correctly without a second stylesheet.
- Dates use Carbon's locale-aware formatting.

## 7. Interface

- Identity: a clinical lab report. White paper, cobalt `#1F4FD8` ink, slate
  `#3B4252` text, report-grey `#F3F5F8` panels and semantic pass `#1E8E5A` /
  warn `#C98A06` / fail `#C8352E`. Full brand notes in
  [BRAND.md](BRAND.md).
- Original SVG logo: a magnifier over a checkmark-shaped heartbeat; the favicon
  is the same mark on a cobalt tile.
- Self-hosted fonts via `@fontsource` (OFL): Noto Sans Arabic Variable for UI and
  Arabic text, IBM Plex Mono for records, headers and raw values.
- Design tokens live in `resources/css/app.css` under `@theme`; components
  (`panel`, `section-label`, `grade-stamp`, `chip-*`, `score-ring`, `finding`,
  `data-table`, `kv`, `alert-*`, `pagination-*`) are a small Tailwind component
  layer. Corners are 4–6 px, borders are hairlines, shadows are minimal.
- Interaction layer: a ~1 KB vanilla module (`resources/js/app.js`) driven by
  `data-*` attributes (mobile menu, confirm dialogs, scan busy state, print
  button). No framework, no inline scripts — this enables `script-src 'self'`.
- Homepage: a single scan console plus a reference list of the five sections, no
  marketing hero or feature-card grid.
- Responsive: single-column mobile, asymmetric tool/reference columns on
  desktop; the history table becomes cards on mobile.
- Accessibility: skip link, visible focus rings, labelled inputs, error messages
  tied with `aria-describedby`, `aria-current`, status regions, semantic
  headings, colour never used alone, reduced-motion support.
- Print stylesheet hides actions, forces evidence open, avoids page breaks inside
  sections and removes the grade-stamp rotation.

## 8. Testing architecture

`tests/Support/NetworkFakes` binds fake implementations of `HostResolver`, `DnsClient`, `HttpTransport` and `TlsInspector` into the container, so no test can touch the network. `NetworkFakes::healthyExampleSite()` configures a full response set (DNS records, certificate, HTML, headers, robots, sitemap).

Coverage:

- Every blocked IP range, IPv4-mapped/compatible/NAT64/6to4 addresses, public controls.
- URL policy (schemes, credentials, ports, internal suffixes, obfuscated IPs, IDN).
- Redirect chains, internal redirects, rebinding with a stateful resolver, loops, max redirects, per-hop pinning.
- SPF/DMARC parsers, header analyzer, SEO parser, scoring, DNS wire format.
- Validation, locale/RTL, history ownership/search/pagination, rescan, JSON export, rate limiting, CSRF token presence.
- A complete scan pipeline with translations checked in both locales.

## 9. Operational behaviour

| Concern | Behaviour |
| --- | --- |
| Scan duration | Typically 2–10 s; hard per-request timeouts (5 s connect, 12 s request), fetch deadline, TLS deadline (~8 s), DNS budget 8 s |
| Response size | Headers ≤ 64 KiB, body ≤ 512 KiB, redirects ≤ 5 |
| Concurrency | No queue; PHP handles requests; rate limiting is the only throttle |
| Storage | SQLite JSON rows; no external services |
| Logging | Section failures and HTTP check failures logged as warnings with exception class/message; no raw secrets |
| Failure UX | Form-level translated error for blocked/unresolvable targets; per-section error banner otherwise |

## 10. Directory map

```
app/
  Domain/SiteCheck/{Checks,Dto,Parsers,Scoring,ScanService.php}
  Http/{Controllers,Middleware,Presenters,Requests}
  Models/Scan.php
  Providers/{AppServiceProvider,SiteCheckServiceProvider}.php
  Services/ScanOwnership.php
  Support/Network/{Http,Dns,Tls,*.php}
config/sitecheck.php
database/{migrations,factories}
lang/{ar,en}/sitecheck.php
resources/{css,js,views}
tests/{Unit,Feature,Support}
docs/{ARCHITECTURE,DECISIONS,BRAND,SECURITY-REVIEW}.md
docs/screenshots/*.png
```

## 11. Security headers and demo mode

`Http/Middleware/SecurityHeaders` runs on every web response and sets a strict
CSP (`script-src 'self'`), `nosniff`, `X-Frame-Options: DENY`, Referrer-Policy,
Permissions-Policy, COOP, CORP and Permitted-Cross-Domain-Policies. HSTS is added
only when the request is secure or `SITECHECK_HSTS=true`. When the Vite dev
server is running (`public/hot`), its origin is added to `script-src` and
`connect-src` so HMR works; production uses same-origin assets only.

Demo mode is read from `config('sitecheck.demo_mode')` (`DEMO_MODE`, default
true). It adds a quiet footer note, seeds fictional sample scans via
`App\Services\DemoData` and registers an hourly `demo:reset` command in
`routes/console.php`; the command truncates the `scans` table and reseeds the
samples. Sample rows are owned by a dedicated token, shown alongside the
visitor’s own history, marked “Sample” and read-only. No demo accounts exist.
