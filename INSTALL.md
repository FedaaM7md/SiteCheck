# Installation — SiteCheck

Everything below was run locally on Windows with the fixed Fedaa toolchain. No
PATH, registry, php.ini, global npm or global Composer configuration was changed.

## 1. Verified environment

| Tool | Version |
| --- | --- |
| PHP | 8.4.28 (NTS VC++ 2022 x64), `php` |
| Composer | 2.11.2 (`composer`) |
| Node.js | 24.12.0 |
| npm | 11.6.2 |
| Git | 2.51.1 |
| Laravel | 13.43.0 |
| OS | Windows (PowerShell) |

Required PHP extensions (verified with `php -m`): `openssl`, `curl`,
`mbstring`, `intl`, `dom`, `xml`, `json`, `pdo_sqlite`, `sqlite3`, `fileinfo`,
`zlib`, `gd`.

## 2. Project and repository

The project was created in release 1.0 with:

```powershell
composer create-project laravel/laravel sitecheck --no-interaction
```

The repository is local only (no remote, no GitHub):

```powershell
cd sitecheck
git init
git config user.name "Fedaa Mohamed"
git config user.email "hello@fedaamohamed.com"
```

## 3. Environment file

```powershell
cd sitecheck
Copy-Item .env.example .env
php artisan key:generate
```

`.env` is ignored by Git. `.env.example` contains only dummy values:
`APP_DEBUG=false`, a placeholder `APP_KEY`, SQLite, a logging mailer, and
`DEMO_MODE=true`. Replace the key with `php artisan key:generate`.

Configuration highlights:

- `APP_NAME="SiteCheck"`, `APP_LOCALE=ar`, `APP_FALLBACK_LOCALE=en`.
- `DB_CONNECTION=sqlite` using `database/database.sqlite`.
- `SESSION_DRIVER=database`.
- `DEMO_MODE=true` shows the reset notice and schedules the hourly reset.
- `SITECHECK_HSTS=false` keeps HSTS off for plain-HTTP local use.
- Network limits live in `config/sitecheck.php`, overridable with `SITECHECK_*`.

## 4. Database

```powershell
php artisan migrate --force
```

This creates the framework tables plus `scans` (private history rows with JSON
reports).

## 5. Fonts (self-hosted via npm)

release 1.1 replaced the single bundled Cairo TTF with two OFL families installed from
npm and bundled by Vite:

```powershell
npm install @fontsource-variable/noto-sans-arabic@5.3.0 @fontsource/ibm-plex-mono@5.3.0
```

- `@fontsource-variable/noto-sans-arabic` — UI and Arabic body text (variable weight).
- `@fontsource/ibm-plex-mono` — records, headers and raw values (400/600 Latin).

They are imported in `resources/css/app.css` and copied into
`public/build/assets/` by Vite. No font is requested from a CDN at runtime. The
OFL licences are inside each npm package; see
[THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).

## 6. Front-end dependencies and build

```powershell
npm install
npm run build
```

release 1.1 removed Alpine.js; the small interaction layer is now vanilla JavaScript
in `resources/js/app.js`, which allows a strict `script-src 'self'` policy.

## 7. Run the test suite

```powershell
php artisan test
```

Result at delivery: **199 tests passed, 877 assertions**. All network access is
mocked in tests.

## 8. Style and audits

```powershell
php vendor\bin\pint --test
composer audit
npm audit
```

All three are clean at delivery.

## 9. Start the local server

Assigned port for this project: **8102**.

```powershell
php artisan serve --host=127.0.0.1 --port=8102
```

Open <http://127.0.0.1:8102>. Stop with `Ctrl+C`. The server is not required for
tests.

## 10. Demo mode reset

```powershell
php artisan demo:reset
php artisan schedule:work
```

`demo:reset` clears the private scan history and, with `DEMO_MODE=true`, reseeds
24 fictional sample scans (reserved `.example` hosts, spread over four months).
With `DEMO_MODE=true`, `schedule:work` runs it every hour. The sample rows are
read-only and marked “Sample”; the public pages show only a tiny footer note.

## 11. First scan

1. Enter a public domain such as `example.com`.
2. Optionally add DKIM selectors under **Advanced options** (comma separated, max 5).
3. Wait a few seconds; the report is stored privately in the current browser session.
4. Use **Print**, **Download JSON**, **Scan again** or **Delete** from the report page.

## 12. Troubleshooting

- **"The domain could not be resolved"** — the target has no public A/AAAA
  record, or the local resolver is unavailable.
- **HTTP request fails but the site works in a browser** — this environment can
  intermittently drop connections to some anycast IPs. The client retries other
  validated addresses; raise `SITECHECK_CONNECT_TIMEOUT` / `SITECHECK_REQUEST_TIMEOUT`.
- **CAA unavailable** — the raw CAA lookup needs the system resolver. Set
  `SITECHECK_DNS_RESOLVERS` (comma separated) if `ipconfig /all` exposes none.
- **Fonts look wrong** — run `npm run build` and hard-refresh; fonts come from
  the Vite bundle.
- **419 on submit** — the session or CSRF token expired; reload the page.

## 13. Production notes

This is a demo, not a production deployment. If you expose it:

- serve over HTTPS, set `SESSION_SECURE_COOKIE=true` and `SITECHECK_HSTS=true`;
- set `APP_ENV=production`, `APP_DEBUG=false`, and a real `APP_URL`;
- use a production cache/session store and a reverse proxy with request limits;
- run `php artisan config:cache` and `php artisan route:cache` after configuration;
- review [SECURITY.md](SECURITY.md) and [docs/SECURITY-REVIEW.md](docs/SECURITY-REVIEW.md) first.
