# Roadmap — SiteCheck

This list is deliberately honest. It reflects the release 1.1 state (2026-11-24).

## Done

- Real DNS checks: A, AAAA, CNAME, NS, MX, TXT, CAA (raw DNS fallback), SOA.
- Real TLS inspection: certificate, SANs, issuer, validity, chain, hostname,
  protocol, cipher.
- SPF / DMARC / DKIM / MX analysis with bilingual explanations.
- HTTP headers, cookies, redirects, HTTPS enforcement, HSTS, CSP, framing,
  referrer, permissions, server leakage, response time.
- SEO basics with local HTML parsing, robots.txt and sitemap probes.
- Weighted scoring, A–F grades, actionable fixes in Arabic and English.
- Private session-scoped history with search, re-scan, delete, print and JSON export.
- Complete SSRF guard with redirect re-validation, IP pinning, DNS-rebinding
  defense and rate limiting.
- **release 1.1 identity:** clinical lab-report design system, original magnifier /
  heartbeat logo and favicon, Noto Sans Arabic + IBM Plex Mono self-hosted via
  `@fontsource`, `docs/BRAND.md`.
- **release 1.1 security:** strict CSP (`script-src 'self'`), no inline scripts or
  handlers, Alpine removed, COOP/CORP/Permitted-Cross-Domain headers, HSTS
  gated on HTTPS, `docs/SECURITY-REVIEW.md`.
- **release 1.1 ownership:** proprietary `LICENSE`, `COPYRIGHT.md`, footer copyright,
  `proprietary`/`UNLICENSED` package metadata, author recorded.
- **release 1.1 demo mode:** `DEMO_MODE`, persistent notice, `demo:reset`, hourly schedule.
- **release 1.1 screenshots:** real ar/en desktop + mobile captures in `docs/screenshots/`.
- 199 network-mocked tests and a production asset build.

## Missing / not production-ready

| Area | Status | Notes |
| --- | --- | --- |
| Authentication and user accounts | Missing | History is session-scoped only. Multi-user hosting needs auth and ownership migration. |
| Background scans | Missing | Scans run synchronously in the request. A queue with workers and progress polling is needed for scale. |
| Scheduled monitoring | Missing | No cron monitoring, alerting or change detection. |
| Historical trends | Missing | History rows exist, but no charts or diffs between scans. |
| PDF export | Missing | Print view only; no server-side PDF generation. |
| DNSSEC validation | Missing | Not checked. |
| Full SPF recursion | Partial | The lookup budget is parsed statically; includes are not resolved recursively. |
| DMARC report ingestion | Missing | rua addresses are validated, but aggregate XML reports are not collected. |
| DKIM auto-discovery | Partial | Common selectors and up to five user selectors are checked; no provider-specific discovery. |
| IPv6 HTTP fallback | Partial | IPv6-only targets work when the client can reach them; fallback ordering prefers IPv4 when available. |
| TLS protocol matrix | Partial | One negotiated connection is inspected; legacy protocol probing is not exhaustive. |
| Chunked/gzip transport unit tests | Missing | The transport was exercised against live sites; wire-level parsing lacks fixture tests. |
| Screen-reader audit | Missing | Semantic HTML, labels, focus styles and contrast were implemented; no assistive-technology audit was run. |
| Arabic copy review | Partial | Written for this project; a native editorial review is still desirable. |
| Production deployment | Missing | Local SQLite, database cache, no reverse proxy, no HTTPS for the app itself. |
| Scan data retention | Partial | `demo:reset` clears and reseeds sample history for the demo; no production retention policy. |
| Abuse controls | Partial | Rate limits exist; no CAPTCHA, no global concurrency budget, no egress firewall. |
| CI pipeline | Missing | Tests/build are documented but not wired to a CI service (and GitHub is intentionally not used). |
| More locales | Missing | Arabic and English only. |
| Structured data / Core Web Vitals | Missing | SEO section covers classic on-page basics only. |
| robots.txt/sitemap semantics | Partial | Presence and basic XML validity only, not rule conflict analysis. |

## Next milestones (suggested order)

1. Queue-based scans with progress states and cancellation.
2. Authentication plus per-user history and quotas.
3. Transport fixture tests (chunked, gzip, truncated, slow headers).
4. Trend charts and scan-to-scan diffs.
5. Server-side PDF and a stable JSON schema version.
6. Production hardening: HTTPS, secure cookies, shared cache/session, egress firewall.
7. An automated accessibility audit to complement the manual review.
