# urlxo.link

**Live at [https://urlxo.link](https://urlxo.link). Free to use.**

Paste a link on the homepage to shorten it, no account needed. Create a free account to edit your links, see click stats and get API keys ([API docs](https://urlxo.link/docs)).

URL shortener with click analytics and a REST API. Laravel 12, Livewire 3, Tailwind v4, MariaDB and Redis.

## Features

- Anonymous shortening on the landing page (rate limited per IP, honeypot)
- Dashboard: custom alias, title, password, expiry, click limit, pause/resume, QR code (SVG)
- Click stats per link: daily chart, unique visitors, country (Cloudflare `CF-IPCountry`), referrer, device, browser, OS. Bots and link previews are not counted. No IP addresses are stored.
- REST API at `/api/v1` with Sanctum Bearer keys, docs at `/docs`

## How a redirect works

`GET /{code}` resolves the link from Redis (`link:{md5(code)}`, `URLXO_CACHE_TTL`), answers with a `302`, and queues a `RecordClick` job on the `clicks` queue. Edits and deletes clear the cache entry. Codes are case sensitive (`utf8mb4_bin` collation).

## Local development

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve --port=8020
php artisan queue:work redis --queue=clicks,default
```

Tests run on in-memory SQLite:

```bash
php artisan test
```

## Deploy (dockxo / any Docker host)

The `Dockerfile` builds a single FrankenPHP image that runs the web server and `CLICK_WORKERS` queue workers (default 2) under supervisord. Migrations run on start unless `RUN_MIGRATIONS=false`.

Required environment: `APP_KEY`, `APP_URL=https://urlxo.link`, `SHORT_DOMAIN=urlxo.link`, `DB_*`, `REDIS_*`. Put Cloudflare in front of the domain so country stats work. For password reset e-mails set `MAIL_MAILER=cloudflare` with `CLOUDFLARE_EMAIL_ACCOUNT_ID` and `CLOUDFLARE_EMAIL_API_TOKEN`.

Optional: `URLXO_GUEST_SHORTEN`, `URLXO_GUEST_PER_HOUR`, `URLXO_API_PER_MINUTE`, `URLXO_BLOCKED_HOSTS` (comma separated), `URLXO_REGISTRATION`, `URLXO_CODE_LENGTH`.
