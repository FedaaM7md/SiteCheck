# Third-party notices — SiteCheck

SiteCheck is proprietary (see [LICENSE](LICENSE)). It uses the following direct
dependencies. All are permitted licences: MIT, BSD-3-Clause, Apache-2.0 or the
SIL Open Font License. No GPL, AGPL or LGPL code is used.

Copyright in the SiteCheck application code, design and copy is held by Fedaa
Mohamed (see [COPYRIGHT.md](COPYRIGHT.md)). Third-party components below remain
the property of their respective owners.

## PHP (Composer)

| Package | Version | License | Purpose |
| --- | --- | --- | --- |
| laravel/framework | 13.43.0 | MIT | Application framework |
| laravel/tinker | 3.2.0 | MIT | Local REPL (development) |
| fakerphp/faker | 1.24.1 | MIT | Test data (development) |
| laravel/pail | 1.3.0 | MIT | Log viewer (development) |
| laravel/pint | 1.32.3 | MIT | Code style (development) |
| mockery/mockery | 1.6.15 | BSD-3-Clause | Test doubles (development) |
| nunomaduro/collision | 8.9.6 | MIT | CLI error handling (development) |
| phpunit/phpunit | 12.5.45 | BSD-3-Clause | Test runner (development) |

## JavaScript (npm)

| Package | Version | License | Purpose |
| --- | --- | --- | --- |
| tailwindcss | 4.3.5 | MIT | CSS framework (build time) |
| @tailwindcss/vite | 4.3.5 | MIT | Tailwind Vite integration (build time) |
| vite | 8.3.8 | MIT | Asset bundler (build time) |
| laravel-vite-plugin | 3.2.1 | MIT | Laravel/Vite integration (build time) |

Alpine.js was removed in release 1.1 so the app can ship a strict
`script-src 'self'` policy. The interaction layer is now original vanilla
JavaScript (`resources/js/app.js`).

## Fonts (self-hosted via npm, SIL OFL 1.1)

| Package / family | Version | License | Role |
| --- | --- | --- | --- |
| @fontsource-variable/noto-sans-arabic — Noto Sans Arabic Variable | 5.3.0 | SIL OFL 1.1 | UI and Arabic body text |
| @fontsource/ibm-plex-mono — IBM Plex Mono | 5.3.0 | SIL OFL 1.1 | Records, headers and raw values |

Both are imported in `resources/css/app.css` and bundled into
`public/build/assets/` by Vite; no font is fetched from a CDN at runtime. The
full OFL text for each font is included in the corresponding npm package
(`node_modules/@fontsource*/.../LICENSE`).

## Transitive dependencies

Composer's `licenses` report for the complete lock file shows only MIT,
BSD-3-Clause, Apache-2.0, and two dual-licensed Nette packages:

- `nette/schema` and `nette/utils` are offered under **BSD-3-Clause OR
  GPL-2.0-only OR GPL-3.0-only**. SiteCheck uses them exclusively under
  **BSD-3-Clause**; the GPL option is not elected.

npm's lock file for the project's declared dependencies includes only
MIT/BSD/Apache-2.0/ISC packages for the build toolchain (Tailwind/Vite are
build-time only and are not shipped as runtime code). A temporary,
non-declared `puppeteer-core` install was used once to capture documentation
screenshots and was pruned afterwards; it is not a dependency of this project.

## Originality

All application code, the SiteCheck logo and favicon, the interface design and
the bilingual copy were written for this project. No code was copied from other
products or open-source applications. Framework and library usage follows their
public APIs.
