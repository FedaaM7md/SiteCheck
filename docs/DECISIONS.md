# Engineering decisions — Fedaa SiteCheck

Each entry records the context, the decision and its consequences. ADR-001 to
ADR-018 were made during release 1.0. ADR-019 onward were made during release 1.1 and
supersede the earlier identity/stack details where noted.

---

## ADR-001 — Laravel 13 + Blade + Tailwind 4 + Alpine + SQLite

**Context:** The task mandates the stack and PHP 8.4. `create-project` installed Laravel 13.43.0 with Tailwind 4 and Vite 8.

**Decision:** Use the installed stack unchanged, with Blade components, a small Alpine layer (mobile menu, submit state) and SQLite for history.

**Consequences:** Fast local setup and no external services. SQLite and the synchronous scan path are demo-grade, not multi-node production choices.

---

## ADR-002 — Resolve-and-pin SSRF model

**Context:** A URL blacklist is bypassable (DNS rebinding, decimal IPs, redirects, IPv4-mapped IPv6).

**Decision:** Do not trust hostnames at connection time. Normalize the URL, resolve every A/AAAA address locally, reject the entire target if **any** address is non-public, then pin the socket to one validated IP. Manual redirect handling re-runs the full gate on every hop. The transport disables proxies and never follows redirects itself.

**Consequences:** Strong, testable SSRF defense; hostnames with mixed public/private answers are refused (deliberate false positive). Scanning public sites remains allowed by design.

---

## ADR-003 — PHP streams instead of cURL for HTTP

**Context:** This Windows PHP build has no `curl.cainfo`, so libcurl rejects every public HTTPS certificate ("unable to get local issuer certificate"), while PHP's OpenSSL stream layer verifies correctly. Shipping a CA bundle would add a dependency/licence question, and disabling verification is unacceptable.

**Decision:** Implement a small HTTP/1.1 client on `stream_socket_client` with `tls://` + `peer_name`, strict verification, chunked/gzip decoding, body/header caps and deadlines. Delete the unused cURL transport.

**Consequences:** Verification works out of the box and the socket is pinned exactly as intended. We own HTTP framing edge cases (folded headers, exotic transfer encodings) and lack HTTP/2. The client was exercised against live sites; wire-level fixture tests are a roadmap item.

---

## ADR-004 — Try other validated IPs on connection failure

**Context:** During manual testing, some anycast addresses (e.g. Cloudflare in this network) intermittently timed out while another validated address answered immediately. Failing the scan on the first IP produced misleading reports.

**Decision:** `ValidatedUrl` carries every resolved address. The transport tries up to three addresses, all of which already passed the same SSRF gate, within one shared time budget. A peer-IP mismatch is never retried.

**Consequences:** More resilient reports without weakening SSRF policy. Worst-case latency is bounded by the per-hop deadline.

---

## ADR-005 — Raw DNS query for CAA

**Context:** `dns_get_record` on this PHP build has no `DNS_CAA` constant and rejects numeric type 257.

**Decision:** Implement a minimal DNS wire client (query encoder + safe response parser) and send CAA queries to the operating system resolver, discovered from `ipconfig /all` (Windows) or `/etc/resolv.conf`, with `SITECHECK_DNS_RESOLVERS` as an override. If discovery fails, the CAA finding is informational ("unavailable"), not a false "missing record".

**Consequences:** Real CAA data on Windows (verified against `google.com`, `github.com`, `letsencrypt.org`). Resolver discovery is platform-dependent and parsing relies on the English `ipconfig` label; documented in ROADMAP.

---

## ADR-006 — Verification-first TLS inspection

**Context:** To report certificate contents for an invalid certificate, verification must be relaxed; doing that unconditionally would hide real trust failures. Network flakiness must not be reported as a bad certificate.

**Decision:** Attempt full verification on the pinned IP first. Only when the error is certificate-related, repeat the handshake with verification disabled solely to read the certificate, and mark `verified=false` with the certificate error. Network/timeout failures retry another validated IP and never set `verified=false` by themselves.

**Consequences:** Truthful "valid/trusted" status and useful certificate details for broken sites. The unverified handshake sends no application data.

---

## ADR-007 — Session-scoped history without authentication

**Context:** The product is explicitly local, with no login requirement in release 1.0. History must still not leak between visitors; "authorization on every action" is required.

**Decision:** Generate a random 128-bit owner token per session (stored server-side, never in URLs) and scope every history/report/export/delete/rescan action to it. Unauthorized or missing rows both return 404.

**Consequences:** Private-by-default local history and real authorization checks. Anyone with the session cookie can read that history; multi-user hosting needs real auth (ROADMAP).

---

## ADR-008 — Synchronous scans with bounded time

**Context:** No queue worker is available in the fixed local environment, and adding infrastructure was out of scope.

**Decision:** Run scans in the request, but with per-request connect/read timeouts, a fetch deadline, a TLS deadline, a DNS budget and a global scan time limit. The UI shows a submitting state.

**Consequences:** Simple and reliable locally. A slow target occupies a PHP worker; scale requires a queue (ROADMAP).

---

## ADR-009 — Scoring weights and grade thresholds

**Context:** A single "score" is only useful if it reflects risk.

**Decision:** Weight findings per check, grade each section with pass=1, warn=0.5, fail=0, then weight sections: SSL 30, HTTP 25, DNS 15, email 15, SEO 15. Grades: A ≥ 90, B ≥ 80, C ≥ 70, D ≥ 60, otherwise F. Weights live in config.

**Consequences:** Broken TLS/HTTPS dominates the score, matching user impact. The weights are a product judgment, documented and adjustable.

---

## ADR-010 — Severity-specific translation variants

**Context:** A finding id can be a pass in one site and a failure in another; one generic sentence would be misleading.

**Decision:** `Finding::translate()` first looks for `…title.{severity}` / `…detail.{severity}` and falls back to a base key. Where pass and fail need fundamentally different advice (SPF `all`, DMARC policy), separate finding ids are used.

**Consequences:** Precise, natural bilingual text. Translation files are larger, and a parity test enforces key symmetry.

---

## ADR-011 — Original design system, self-hosted font, no CDN

**Context:** Rules forbid CDNs, third-party logos and trackers; the quality bar requires a distinctive identity.

**Decision:** Build a small component layer on Tailwind tokens (indigo/teal/slate), draw an original shield/radar logo, and self-host the Cairo variable font (OFL). Remove the Laravel skeleton's `bunny()` font plugin, which would have fetched a font from a CDN.

**Consequences:** Zero runtime third-party requests, consistent RTL/LTR styling, one 600 KB font asset in the build.

---

## ADR-012 — Null MX means "email disabled"

**Context:** A domain publishing `0 .` explicitly declares that it does not receive mail. Scoring it as a failure and then failing SPF/DMARC would punish a valid configuration.

**Decision:** Detect null MX, award a passing MX finding, add an informational note, and keep SPF/DMARC/DKIM evidence visible but unscored.

**Consequences:** Correct treatment of no-mail domains; the email score stays meaningful.

---

## ADR-013 — DKIM checks are a probe, not a verdict

**Context:** DKIM selectors are arbitrary; checking the apex TXT record cannot prove absence.

**Decision:** Check up to five user-provided selectors, or a set of common selectors when none are given, and word the warning as "no usable key was found for the selectors checked" with a hint that providers may use another selector.

**Consequences:** Useful signal without false certainty.

---

## ADR-014 — Removed unused skeleton instruction files

**Context:** The initial skeleton included auxiliary instruction files for editor integrations that this project does not use, and they conflicted with the fixed environment and no-global-install rules.

**Decision:** Delete those files and rely only on the documented project workflow; do not install additional tooling.

**Consequences:** No environment changes; the project runs from the documented commands only.

---

## ADR-015 — Network-mocked tests, manual live verification

**Context:** Tests must never hit the internet.

**Decision:** Bind fake resolver/DNS/HTTP/TLS implementations through the container. Unit-test parsers and the wire format with fixtures. Verify the real transport, TLS and CAA paths manually against live sites and record the results in TESTING.md.

**Consequences:** Deterministic, fast suite (190 tests, ~1.4 s) and real-world evidence. Wire-level parsing remains the largest uncovered surface.

---

## ADR-016 — DNS budget instead of per-query timeout

**Context:** PHP's synchronous resolver exposes no per-query timeout.

**Decision:** Enforce a wall-clock budget between queries and surface `timeout`; never silently treat budget exhaustion as "record missing" for CAA.

**Consequences:** Bounded scans, with a documented precision trade-off.

---

## ADR-017 — Generic user-facing network errors, detailed logs

**Context:** SSRF rejections must not leak internal topology, but operators need details.

**Decision:** Translate stable reason codes into short bilingual messages; log exception class/message (including the blocked reason) server-side. Never render raw exception text in the UI.

**Consequences:** Safe UX and debuggable failures.

---

## ADR-018 — No screenshots in documentation

**Context:** Rules forbid fake images presented as real.

**Decision:** README includes a screenshots section stating that no images are included yet. No mockup images are included.

**Consequences:** Documentation is honest; visual evidence remains a roadmap item. (Superseded in release 1.1 by ADR-023: real screenshots were captured.)

---

## ADR-019 — Clinical report identity (supersedes the palette/logo in ADR-011)

**Context:** release 1.1 requires a distinct identity per product and forbids the generic
shared Fedaa look.

**Decision:** Rebuild the design system as a lab-report document: white paper,
cobalt `#1F4FD8`, slate `#3B4252`, report-grey `#F3F5F8`, semantic pass/warn/fail.
A new magnifier-over-heartbeat logo, a grade stamp, monospace record type and a
single-console homepage replace the indigo/teal hero, feature-card grid and
how-it-works sections. Tokens and components live in `resources/css/app.css`;
brand rules in `docs/BRAND.md`.

**Consequences:** The product no longer looks like its siblings. Copy and layout
are product-specific (diagnostic, problem/cause/fix), and the homepage is a tool.

---

## ADR-020 — Remove Alpine and inline handlers for a strict CSP

**Context:** release 1.1 requires a CSP with no unsafe-inline scripts where avoidable.
Alpine evaluates expressions with `new Function`, which requires `unsafe-eval`;
the templates also used inline `onsubmit`/`onclick` handlers.

**Decision:** Remove Alpine and replace its four behaviours (mobile menu, confirm
dialogs, scan busy state, print) with a ~1 KB vanilla module driven by `data-*`
attributes. Delete all inline handlers. Ship `script-src 'self'` (adding the Vite
dev origin only when `public/hot` exists). Allow inline styles only for the score
ring's `--score` custom property.

**Consequences:** A real strict CSP and no runtime framework. Behaviour degrades
gracefully without JS (the form still submits; confirm becomes no-op). A test
asserts rendered pages contain no inline handlers.

---

## ADR-021 — Fonts from @fontsource instead of a bundled TTF

**Context:** release 1.1 mandates self-hosted OFL fonts via `@fontsource/*`. The release 1.0
build shipped a downloaded Cairo TTF.

**Decision:** Install `@fontsource-variable/noto-sans-arabic` and
`@fontsource/ibm-plex-mono` (both OFL 1.1), import them from `app.css`, and let
Vite bundle the woff2 files. Remove `resources/fonts/` (Cairo).

**Consequences:** Two families with proper licensing/versioning and per-subset
`unicode-range` loading. The bundle carries only the subsets a page uses.

---

## ADR-022 — Demo mode as env + reset command + hourly schedule

**Context:** release 1.1 asks for a demo mode with a reset command and scheduled run.
SiteCheck has no accounts, so there is no login to hide.

**Decision:** Read `DEMO_MODE` (default true) into `config('sitecheck.demo_mode')`.
Show a persistent notice and schedule `demo:reset` hourly; the command truncates
the private `scans` table. Setting `DEMO_MODE=false` hides the notice and disables
the schedule.

**Consequences:** A shared demo does not accumulate visitors' history. There are
no seeded/fake rows; the notice is honest about the reset.

---

## ADR-023 — Explicit ownership, proprietary licence and real screenshots

**Context:** release 1.1 requires clear ownership and honest visual evidence.

**Decision:** Add a proprietary `LICENSE` (Fedaa Mohamed, 2026), `COPYRIGHT.md`,
a footer copyright, copyright headers on authored source files, and
`proprietary`/`UNLICENSED` metadata with the author recorded. Capture real ar/en
desktop and mobile screenshots with headless Chrome (a temporary `puppeteer-core`
install, pruned afterwards) and commit them under `docs/screenshots/`.

**Consequences:** Ownership is unambiguous, no third-party code is claimed, and
the README shows real UI rather than mockups.

---

## ADR-024 — Owner credit in the footer, product pages and sample data

**Context:** The release 1.1 addendum (which supersedes the “by Fedaa” logo line)
asks products to stand alone, credit the owner only in a footer link, and look
like a real product with about/policy/help pages and believable sample data.

**Decision:** Remove the owner line from the header/print header. The footer
bottom line reads “© 2026 SiteCheck. All rights reserved.” plus a separate muted
link “Designed & built by Fedaa Mohamed” → `https://fedaamohamed.com` (Arabic:
“تصميم وتطوير: فداء محمد”). Add About, Privacy, Terms, Help/FAQ and Changelog
pages with per-page title, description, canonical and Open Graph tags, plus a
1200×630 `og-image.png`. Replace the loud top demo bar with a tiny footer note;
seed 24 fictional `.example` scans (varied scores, spread over four months) via
`App\Services\DemoData`, shown alongside the visitor’s own history, marked
“Sample” and read-only.

**Consequences:** The brand reads as a product, not a portfolio piece. Demo
honesty is preserved through the footer note and per-row sample chips. Legal
ownership stays in `LICENSE`, `COPYRIGHT.md`, package metadata and the README.
