# Brand — SiteCheck

SiteCheck is a diagnostic tool. The identity is a **clinical audit report**: a
white lab document with a cobalt ink, monospaced records and a graded stamp.
It should read like a result sheet, not a marketing page.

## 1. Logo

- **Concept:** a magnifier over a checkmark-shaped heartbeat.
- The lens circle and handle form the magnifier; the line inside is an ECG
  pulse that ends as a checkmark. One continuous stroke, one colour.
- The mark is monochrome cobalt `#1F4FD8` on white (or white on cobalt for the
  favicon tile).
- The wordmark is the Latin name **SiteCheck**. The product name stands alone:
  **no owner line under or beside the logo** in the header, page titles or print
  header.
- Owner credit lives only in the footer bottom line: “© 2026 SiteCheck. All
  rights reserved.” followed by a separate muted link “Designed & built by Fedaa
  Mohamed” → `https://fedaamohamed.com` (and the Arabic equivalent “تصميم وتطوير:
  فداء محمد”).
- Files: `resources/views/components/logo.blade.php` (mark) and
  `public/favicon.svg` (favicon tile).

## 2. Colour

| Token | Hex | Use |
| --- | --- | --- |
| Paper | `#FFFFFF` | Page and panel background |
| Cobalt | `#1F4FD8` | Primary actions, links, focus, logo, key lines |
| Slate ink | `#3B4252` | Body and heading text |
| Soft | `#66707F` | Secondary text, captions |
| Faint | `#98A1B0` | Placeholders, tertiary metadata |
| Report grey | `#F3F5F8` | Section header strips, record backgrounds |
| Line | `#DDE3EA` | Hairline borders |
| Pass | `#1E8E5A` | Passing checks (with icon + label, never colour alone) |
| Warn | `#C98A06` | Warnings (with icon + label) |
| Fail | `#C8352E` | Failures (with icon + label) |
| Info | `#486581` | Informational findings |

Cobalt scale (`brand-50 … brand-950`) is derived from `#1F4FD8` and lives in
`resources/css/app.css` under `@theme`.

## 3. Typography

| Role | Family | Notes |
| --- | --- | --- |
| UI / body / Arabic | **Noto Sans Arabic Variable** | Self-hosted via `@fontsource-variable/noto-sans-arabic`, OFL 1.1 |
| Records / headers / raw values | **IBM Plex Mono** 400 / 600 | Self-hosted via `@fontsource/ibm-plex-mono`, OFL 1.1 |

- Scores, durations, IPs, hosts, header names, DNS types and grades are set in
  IBM Plex Mono with `tabular-nums`.
- Latin technical values always carry `dir="ltr"`.
- Arabic and English text share one scale; no letter-spacing is applied to
  Arabic body text.

## 4. Shape and layout

- Small radii (4–6 px), hairline borders, almost no shadow. Paper, not glass.
- Section headers are uppercase monospace strips on report grey.
- The homepage is a single **scan console** with a reference list beside it —
  no marketing hero, no feature-card grid.
- The report is a printable document: score ring, grade stamp, per-section
  scores, collapsible raw evidence.
- Asymmetry is intentional: a wide tool column and a narrower reference column.

## 5. Voice

Diagnostic, plain and actionable. State the observation, then the cause, then
the fix. Short sentences. Real numbers. No hype and no filler.

**Do**

- "The certificate expires in 12 days. Renew it and check automatic renewal."
- "SPF ends with `~all`, so unauthorised mail is marked but not rejected."
- "افحص موقعك" · "المشكلة / السبب / الحل"

**Don't**

- "Seamless", "elevate", "unlock", "next-level", "all-in-one".
- "بكل سهولة", "حلول متكاملة", "بضغطة زر", "ارتقِ".
- Gradients, blobs, floating mock cards, emoji, sparkle badges.

## 6. Glossary (Arabic / English)

| English | Arabic |
| --- | --- |
| Scan / site check | فحص |
| Report | تقرير |
| Grade | تقدير |
| Score | درجة |
| Finding | نتيجة |
| Pass / warning / failure | ناجح / تحذير / إخفاق |
| Evidence | أدلة |
| Certificate | شهادة |
| Security headers | ترويسات الأمان |
| Fix | طريقة الإصلاح |

Keep these terms consistent across `lang/ar/sitecheck.php` and the interface.
