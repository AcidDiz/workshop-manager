# START - Workshop Manager

Main guide to install and run the project.

## Contents

- [Technical stack](#technical-stack)
- [Local requirements](#local-requirements)
- [Step-by-step installation](#step-by-step-installation)
- [Running in development](#running-in-development)
- [Quality checks and testing](#quality-checks-and-testing)
- [Quick troubleshooting](#quick-troubleshooting)

## Technical stack

- Backend: Laravel 13
- Auth backend: Laravel Fortify
- Frontend bridge: Inertia.js v3
- Frontend UI: Vue 3 + TypeScript
- Styling: Tailwind CSS v4
- TypeScript routing helper: Laravel Wayfinder
- Testing: Pest v4
- Build tool: Vite
- Queue default: `database`
- Session driver default: `database`
- Cache store default: `database`
- Mailer default: `log`
- Recommended cross-platform environment: Laravel Sail with MySQL and Redis

## Local requirements

You need at least:

- PHP 8.3+
- Composer 2.x
- Node.js 20+ with npm
- MySQL (via Sail or a local install) for application development
- Typical Laravel PHP extensions, including PDO MySQL, **pdo_sqlite** (needed for `php artisan test` on the host if you run tests outside Docker), mbstring, openssl, and tokenizer

For Laravel Sail, also:

- Docker
- Docker Compose

This project’s Sail setup includes:

- MySQL
- Redis

Practical notes:

- Prefer Sail with MySQL and Redis as the main development environment.
- The test suite defaults to SQLite in-memory (see `phpunit.xml`), independent of your dev database.
- To run tests against MySQL (e.g. engine-specific checks), set `DB_CONNECTION=mysql` and the `DB_*` variables before `php artisan test`.

## Step-by-step installation

### 1. Go to the application root

```bash
cd workshop-manager
```

### 2. Install backend dependencies

```bash
composer install
```

### 3. Install frontend dependencies

```bash
npm install
```

### 4. Create the environment file

```bash
cp .env.example .env
```

### 5. Choose how you run the app

#### Recommended: Laravel Sail

Laravel Sail works well when you want a consistent environment on macOS, Linux, and Windows without heavy local configuration.

This project uses Sail with:

- MySQL
- Redis

The repo already includes `compose.yaml` with the required services.

Start the stack:

```bash
docker compose up -d
```

Or use the Sail script:

```bash
./vendor/bin/sail up -d
```

Run migrations inside the container:

```bash
./vendor/bin/sail artisan migrate
```

Optional demo or other seeds:

```bash
./vendor/bin/sail artisan db:seed
```

#### Local option: MySQL without Docker

If you do not use Sail, configure MySQL in `.env`, for example:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workshop_manager
DB_USERNAME=...
DB_PASSWORD=...
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### 6. Generate the application key

```bash
php artisan key:generate
```

### 7. Run migrations

```bash
php artisan migrate
```

### 8. Build assets or start the dev server

One-off production build:

```bash
npm run build
```

Interactive development:

```bash
composer run dev
```

## Running in development

Recommended local workflow:

```bash
composer run dev
```

This starts:

- Laravel server
- Queue worker
- Laravel Pail (logs)
- Vite dev server

With Sail, a practical equivalent is:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan queue:listen --tries=1 --timeout=0
./vendor/bin/sail artisan pail --timeout=0
```

To run processes separately:

```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1 --timeout=0
php artisan pail --timeout=0
```

### Dashboards

- **`GET /dashboard`** (`dashboard`) is Fortify’s **home** URL: middleware **`redirect.dashboard_home`** sends users to **`admin.dashboard`**, **`app.dashboard`**, or **`profile.edit`** depending on workshop abilities (no Inertia page on `/dashboard` itself).
- **`GET /app/dashboard`** (`app.dashboard`) is the **employee workshop home**: Inertia `app/dashboard/Index` with **`registrationSummary`** (confirmed and waiting-list counts for the signed-in user). Requires **`can:viewAny,Workshop`** (same middleware group as **`GET /app/workshops`**).
- **`GET /admin/dashboard`** (`admin.dashboard`) is the **workshop admin overview**: initial aggregate counts come from Inertia props (`statistics`); live updates use **Laravel Reverb** and **Laravel Echo** on the private channel `admin.workshop-statistics` (event `statistics.updated`).

### Laravel Reverb (realtime)

Realtime features expect **`BROADCAST_CONNECTION=reverb`** (see `.env.example`) and a consistent split between **server-side** and **browser-side** Reverb settings.

**Why two host/port pairs?**

- **`REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME`** are used by **Laravel (PHP)** when it publishes events to Reverb over HTTP (Pusher-compatible API). Inside Sail, PHP runs in the `laravel.test` container: **`localhost` there is the app container itself**, not Reverb. Setting `REVERB_HOST=localhost` typically causes `BroadcastException` / cURL “connection refused” to port 8080.
- **`VITE_REVERB_HOST` / `VITE_REVERB_PORT` / `VITE_REVERB_SCHEME`** are baked into the **frontend** bundle for **Laravel Echo** (the browser’s WebSocket client). The browser must use a hostname and port it can actually reach on your machine (published port), not the Docker service name `reverb`.

The workshop admin statistics stream is broadcast **immediately after the database transaction commits** (no queue worker required for that UI update). **`Workshop` `updated` events** only trigger a refresh when `starts_at`, `ends_at`, or `title` change (metrics-relevant fields); purely cosmetic edits such as **description** do not publish. **`WorkshopRegistration` `updated`** broadcasts when `status`, `workshop_id`, or `user_id` change.

#### `.env` with Laravel Sail (recommended)

Start Reverb (and the app) with Sail, e.g. `./vendor/bin/sail up -d` including the **`reverb`** service from `compose.yaml`. Reverb listens on **8080 inside the container**; the host maps **`${FORWARD_REVERB_PORT:-8081}:8080`** (default published port **8081**).

Use a block like this (adjust `REVERB_APP_*` secrets as needed):

```dotenv
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=workshop-manager
REVERB_APP_KEY=your-reverb-key
REVERB_APP_SECRET=your-reverb-secret

# PHP → Reverb over the Docker network (service name + internal port)
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http

# Browser → WebSocket on the host (must match the published port, default 8081)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8081
VITE_REVERB_SCHEME=http
```

If you open the app at a custom host such as **`http://workshop-manager.local:8080`**, set **`VITE_REVERB_HOST=workshop-manager.local`** (same hostname the browser uses) so cookies and same-site behaviour stay consistent; keep **`VITE_REVERB_PORT`** on the **published Reverb port** (often **8081**), not `APP_PORT`.

After editing `.env`:

```bash
./vendor/bin/sail artisan optimize:clear
npm run build
```

(or `npm run dev` during development so `VITE_*` are picked up)

#### `.env` without Docker (e.g. `composer run dev`)

When PHP and Reverb both run on the host, **`REVERB_*` and `VITE_REVERB_*` can match** (same host and port), for example:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_HOST=127.0.0.1
REVERB_PORT=8081
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8081
VITE_REVERB_SCHEME=http
```

`composer run dev` starts Reverb on **`127.0.0.1:8081`** in this project’s script; align `REVERB_PORT` / `VITE_REVERB_PORT` with that.

#### Troubleshooting

| Symptom | Likely cause |
| ------- | ------------ |
| `Failed to connect to localhost port 8080` from PHP while using Sail | `REVERB_HOST` is `localhost` inside the container; use **`reverb`** (or the correct Docker hostname) and **`REVERB_PORT=8080`**. |
| Echo connects but auth fails | Session / CSRF: ensure **`csrf_token`** is available to Echo (Inertia shared props) and you are on the same site as **`APP_URL`**. |
| No live updates after changing `.env` | Run **`php artisan optimize:clear`** (or Sail equivalent) and **rebuild or restart Vite** so `VITE_*` changes apply. |

Tests set **`BROADCAST_CONNECTION=null`** in `phpunit.xml`, so the suite does not require a socket server.

### Workshop reminder emails (scheduler)

The app registers **`workshops:remind`** in `routes/console.php` to run **daily at 07:00** (application timezone). It emails **confirmed** participants only, for workshops whose **`starts_at`** falls on the **next calendar day** in `config('app.timezone')`—the decision is **date-based**, not “exactly N hours before”.

Production requires a system cron entry that runs Laravel’s scheduler every minute, for example:

```bash
* * * * * cd /path/to/workshop-manager && php artisan schedule:run >> /dev/null 2>&1
```

Manual runs (e.g. with Sail): `./vendor/bin/sail artisan workshops:remind`. Ensure `MAIL_*` is configured so notifications can be delivered.

**Idempotency (same calendar day, app timezone):** each `(workshop, user, reminder kind, dispatch_date)` is stored in `workshop_reminder_dispatches` before the notification is sent. A second run of `workshops:remind`, a second click on “send reminders” for the same workshop, or a mix of both on the **same local day** will not email the same participant again for that kind. To intentionally resend on the same day (e.g. after a failed mailer), delete the relevant rows in that table or wait until the next calendar day.

## Quality checks and testing

### Application tests

Tests default to SQLite in-memory (`phpunit.xml`). To run Pest/PHPUnit without Pint first:

```bash
composer run test:php -- --compact
```

Or:

```bash
php artisan test --compact
```

With Sail (same SQLite-in-memory default):

```bash
./vendor/bin/sail artisan test --compact
```

To force tests against Sail’s MySQL (variables as already set in the container):

```bash
DB_CONNECTION=mysql DB_HOST=mysql DB_DATABASE=testing ./vendor/bin/sail artisan test --compact
```

### Browser tests (Pest + Playwright)

Tests under `tests/Browser` are not part of the default `php artisan test` run (only `Unit` and `Feature` in `phpunit.xml`). Run them with:

```bash
npm install
npx playwright install
composer run test:browser -- --compact
```

With Sail, from the application root:

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npx playwright install
./vendor/bin/sail composer run test:browser -- --compact
```

### Frontend checks

```bash
npm run lint:check
npm run format:check
npm run types:check
```

### Full check

```bash
composer run test
composer run ci:check
```

## Quick troubleshooting

### Missing Vite manifest

Run:

```bash
npm run build
```

Or, in development:

```bash
npm run dev
```

### Login or protected pages fail after setup

Check:

- `.env`
- `APP_KEY`
- Migrations have run
- The `sessions` table exists

### Queue worker not running

Queues use the `database` driver: the `jobs` table must exist and the worker must be running.

### Email not delivered

`.env.example` is set up for **Mailtrap** (`MAIL_MAILER=smtp`, `sandbox.smtp.mailtrap.io`): add your inbox credentials, then set `MAIL_LOG_OUTGOING=true` to also write a **summary** (subject, recipients, body preview) to `storage/logs/mail.log` when `MAIL_OUTGOING_LOG_CHANNEL=mail`, or leave `MAIL_OUTGOING_LOG_CHANNEL` empty to use the default log stack.

For log-only delivery (no SMTP), use `MAIL_MAILER=log` and `MAIL_LOG_OUTGOING=false` to avoid duplicate log lines.

### Cache or sessions break the app after setup

With `CACHE_STORE=database` and `SESSION_DRIVER=database`, run migrations successfully before using the app.

### Tests: `could not find driver` with SQLite

The suite sets `DB_CONNECTION=sqlite` in `phpunit.xml`. On host PHP, install the SQLite extension (e.g. on Debian/Ubuntu `php8.3-sqlite3`), or run tests inside the Sail container, which already includes the drivers.
