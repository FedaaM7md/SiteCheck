# Testing — SiteCheck

All results below are real and were produced on **2026-11-24** in
`sitecheck` with the fixed toolchain. The automated
suite never touches the network.

## 1. Environment

| Item | Value |
| --- | --- |
| PHP | 8.4.28 (`php`) |
| Laravel | 13.43.0 |
| PHPUnit | 12.5.45 |
| Node | 24.12.0 |
| npm | 11.6.2 |
| SQLite | bundled extension, `:memory:` for tests |
| OS | Windows, PowerShell |

## 2. Automated test suite

```powershell
php artisan test
```

Final result:

```
Tests:    205 passed (916 assertions)
Duration: 2.44s
```

Zero failures, zero errors, zero skipped tests. All network access is mocked via
`tests/Support/NetworkFakes`.

### What is covered

| Area | Tests |
| --- | --- |
| IP policy | Every blocked IPv4 range, blocked IPv6 (ULA, link-local, multicast, documentation, Teredo, ORCHID, discard, 3fff), IPv4-mapped, IPv4-compatible, NAT64, 6to4, public controls and malformed input |
| URL policy | schemes, credentials, ports, internal/reserved suffixes, single labels, obfuscated decimal/hex/octal IPs, IDN, fragments, default-port normalization, over-long input |
| Redirects | relative/dot-segment resolution, every hop pinned, redirect to private/reserved host, disallowed scheme, credential redirect, loop detection, max-5 limit, transport errors |
| DNS rebinding | stateful mocked resolver returns a public IP then a private one; the second resolution is rejected |
| HTTP fetcher | final URL, hop count, bodies, per-hop pinning, failure wrapping |
| DNS wire format | CAA query encoding, compressed answer parsing, non-CAA records, truncation, malformed packets, pointer loops |
| SPF | valid record, `+all`, `~all`, `?all`, missing `all`, multiple records, >10 lookups, ptr, redirect, unknown mechanism/modifier, missing record, case-insensitivity |
| DMARC | reject/quarantine/none, missing/invalid policy, rua parsing, multiple records, pct, duplicate tags, invalid record |
| Headers | HSTS parsing, CSP unsafe directives, frame-ancestors, XCTO, frame options, referrer, permissions, cookie flags, server banners, malformed cookies |
| SEO | metadata, canonical, robots, H1, lang, viewport, Open Graph, image alt, missing elements, empty document, script/resource isolation |
| Scoring | pass/warn/fail weights, info exclusion, zero-weight exclusion, section scores, weighted overall, grades, tones |
| Validation | empty target, scheme, credentials, internal hosts, length, DKIM selector characters, selector cap, CSRF token, accepted target |
| History | session isolation, search, wildcard safety, empty states, cross-session 404s, delete, view, JSON export, rescan, pagination |
| Locale | Arabic default and RTL, English switch and LTR, switch back, invalid locale, language switch rendering, ar/en key parity, security headers (CSP, COOP, CORP) |
| Full scan | complete mocked pipeline, all five sections at 100 on a healthy fixture, persisted report, localized findings, print page, JSON export |
| Rate limiting | named limiter registration and middleware, per-IP 429 page |
| Demo mode | `demo:reset` clears history, notice shown/hidden by config, no inline event handlers in rendered pages |

## 3. Front-end build

```powershell
npm run build
```

Final output:

```
vite v8.3.8 building client environment for production...
✓ 3 modules transformed.
public/build/manifest.json                                                   3.44 kB │ gzip:  0.48 kB
public/build/assets/ibm-plex-mono-latin-400-normal-CvHOgSBP.woff            13.14 kB
public/build/assets/ibm-plex-mono-latin-600-normal-DWFSQ4vo.woff            13.20 kB
public/build/assets/noto-sans-arabic-symbols-wght-normal-BJ6p-EzA.woff2     14.58 kB
public/build/assets/ibm-plex-mono-latin-400-normal-DMJ8VG8y.woff2           14.70 kB
public/build/assets/ibm-plex-mono-latin-600-normal-BgSNZQsw.woff2           15.62 kB
public/build/assets/noto-sans-arabic-latin-ext-wght-normal-CIz31GGw.woff2   16.81 kB
public/build/assets/noto-sans-arabic-math-wght-normal-pS2d8anF.woff2        22.71 kB
public/build/assets/noto-sans-arabic-latin-wght-normal-BQEJrIpl.woff2       31.41 kB
public/build/assets/noto-sans-arabic-arabic-wght-normal-BhzieyvS.woff2     165.96 kB
public/build/assets/app-pm0LdNxc.css                                        52.67 kB │ gzip: 10.10 kB
public/build/assets/app-DM_5APd4.js                                          1.46 kB │ gzip:  0.46 kB
✓ built in 217ms
```

No build warnings or errors. All fonts are bundled locally; the JavaScript entry
is now 1.16 kB because Alpine was removed. No external asset URL appears in the
manifest.

## 4. Style and dependency audits

```powershell
php vendor\bin\pint --test
composer audit
npm audit
```

```json
{"tool":"pint","result":"passed"}
```

- `composer audit`: “No security vulnerability advisories found.”
- `npm audit`: “found 0 vulnerabilities”.

## 5. Live end-to-end verification (manual)

These checks used the real network and the local server on port 8102. They are
**not** part of the automated suite. The server was stopped afterwards.

### 5.1 Real scan and report render

A live scan of `example.com` was submitted through the running UI and produced a
real report:

- Redirect to `/scans/3` then `/scans/4` for a second mobile capture; both
  returned HTTP 200.
- Overall score **76 (C)** for the first capture, with all five sections
  (DNS, SSL/TLS, email, HTTP, SEO), translated findings, problem/cause/fix text
  and raw evidence rendered.
- The response carried the strict CSP and the other hardening headers.
- The browser loaded `favicon.svg`, both self-hosted font families and the
  built CSS/JS from same-origin `/build/assets/...` URLs only.

### 5.2 Header check

A direct request to `/` returned HTTP 200 with:

```
Content-Security-Policy: default-src 'self'; script-src 'self';
style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';
connect-src 'self'; frame-ancestors 'none'; base-uri 'self';
form-action 'self'; object-src 'none'; manifest-src 'self'
```

### 5.3 Screenshots

Screenshots were re-captured on 24 November 2026 from the running demo build with headless Chrome (desktop 1440×900, mobile 390×844, Arabic and English). The current files are in `docs/screenshots/` and are shown in the README.

### 5.4 Fix-round verification (2026-11-24)

Automated browser checks were run against the live app (headless Chrome) after
the review fixes:

- **Arabic typography:** every visible element whose direct text contains Arabic
  was checked; no element used IBM Plex Mono as its first family and no Arabic
  element had non-zero `letter-spacing`. Result: **0 issues** on home, report,
  history and print, in ar and en.
- **Horizontal overflow (`scrollWidth <= innerWidth`):** home, report and history
  at 1366 px and 375 px, in **ar and en**, plus the print view. Result: **all OK**
  (e.g. 375 <= 375). Long TXT/header values wrap or scroll inside their own box.
- **Tap targets at 375 px:** every visible `a[href]`, button, input, select,
  textarea, summary and `[role=button]` measured; the hidden skip-link is
  excluded by design. Result: **0 controls under 44 px** on home, report and
  history.
- **Print:** A4 media emulation shows no navigation or demo banner, the grade
  stamp is visible, tables stay inside the page width, and a real A4 PDF was
  produced.

### 5.5 Review-round 2 — owner credit, pages and realistic demo data (2026-11-24)

Browser checks against the live app after the addendum changes:

- **Owner credit:** the header brand reads only “SiteCheck”; no “من فداء”/“by
  Fedaa” appears near the mark. The footer shows “© 2026 SiteCheck. All rights
  reserved.” and a separate muted credit link “Designed & built by Fedaa
  Mohamed” → `https://fedaamohamed.com` (`rel="noopener"`). The card/print header
  has no owner line.
- **Pages:** `/about`, `/privacy`, `/terms`, `/help`, `/changelog` return 200 in
  both locales. Each page sets its own `<title>`, meta description, canonical,
  Open Graph (`og:title`, `og:description`, `og:url`, `og:image`) and Twitter
  card tags. `public/og-image.png` is a real 1200×630 share image.
- **Demo data:** `demo:reset` seeds 24 clearly fictional scans (reserved
  `.example` hosts) spread across the last four months with varied scores and
  grades. History shows a subtle sample notice, a “عينة / Sample” chip on each
  row, and read-only demo reports (delete/rescan return 404). The top demo banner
  is gone; only a tiny footer note (“نسخة عرض — البيانات افتراضية” / “Demo build —
  sample data”) remains. The home page lists the three most recent scans by
  `scanned_at`.
- **Pagination:** Laravel’s link labels are decoded and localized
  (`lang/{ar,en}/pagination.php`); the duplicated manual prev/next buttons were
  removed.
- **Arabic typography:** 0 issues on the new pages and footer (no mono-first
  Arabic, no non-zero letter-spacing).
- **Layout:** no horizontal overflow at 1366 px or 375 px on home, history,
  report, about, help, privacy, terms and changelog, in ar and en. Tap targets at
  375 px: 0 controls under 44 px on home, history, report, about, help and
  changelog. Print stays within A4, with no nav/demo banner.
- Tests: 205 passed, 916 assertions. Build and audits clean (see sections 2–4).

### 5.6 Review-round 3 — language hygiene, B2 defects and dependencies (2026-11-24)

- **Tool-name scan:** a `git grep` across tracked files for tooling names now
  returns only the standard HTTP `User-Agent` header and the `robots.txt`
  user-agent line — web-standard protocol terms, not tooling references. The
  decision that mentioned editor instruction files was rewritten; future commits
  use the owner identity.
- **B2 re-check (ar/en, 375 px and desktop):**
  - Arabic typography: **0 issues** (no mono-first Arabic, no letter-spacing) on
    home, history, report and every info page.
  - No English UI phrases in the Arabic pages (checked for common labels such as
    “All rights”, “New scan”, “Scan history”, “Privacy policy”, “Previous/Next”);
    only brands, codes and the “English” language switch remain Latin.
  - Mobile history is content-first: the search filter lives behind a “بحث”
    button (`aria-expanded`/`aria-controls`) that opens the panel; no layout jump
    and no overflow. Tables become cards; long hosts truncate; long evidence
    values wrap inside their own boxes.
  - Dates render with Arabic month names and no raw timezone ids; Arabic
    punctuation (، ؟) is used in the copy.
  - No horizontal overflow at 375 px or 1366 px; 0 tap targets under 44 px.
- **Dependencies:** no npm or Composer package was added beyond the framework
  base and the two OFL font packages. Direct Composer deps: `laravel/framework`,
  `laravel/tinker`, `fakerphp/faker`, `laravel/pail`, `laravel/pint`,
  `mockery/mockery`, `nunomaduro/collision`, `phpunit/phpunit`. npm: `tailwindcss`,
  `@tailwindcss/vite`, `vite`, `laravel-vite-plugin`, plus the two `@fontsource`
  packages. An unused development helper was removed; Alpine remains removed for
  the strict CSP.

## 6. What was not tested automatically

- **Wire-level HTTP transport** (chunked transfer, gzip, truncated bodies). It
  was exercised manually against live sites; fixture tests are listed in ROADMAP.
- **Real DNS/TLS timing and resolver discovery** — platform-dependent by nature.
- **Screen-reader testing** — semantic HTML, labels, focus styles and contrast
  were implemented and visually reviewed, but no assistive-technology audit was
  run.
- **Multi-user/concurrency behaviour** — no authentication or queue exists yet.

No failing test was skipped, deleted or weakened. The only unfinished work is the
roadmap items above, explicitly documented.
