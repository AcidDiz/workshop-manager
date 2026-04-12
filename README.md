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

The default mailer is `log`; messages go to the application logs, not a real mailbox.

### Cache or sessions break the app after setup

With `CACHE_STORE=database` and `SESSION_DRIVER=database`, run migrations successfully before using the app.

### Tests: `could not find driver` with SQLite

The suite sets `DB_CONNECTION=sqlite` in `phpunit.xml`. On host PHP, install the SQLite extension (e.g. on Debian/Ubuntu `php8.3-sqlite3`), or run tests inside the Sail container, which already includes the drivers.
