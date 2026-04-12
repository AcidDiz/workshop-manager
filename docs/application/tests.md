# Tests (Pest and PHPUnit)

This application uses **Pest 4** on top of **PHPUnit**, with Laravel’s testing helpers. This document explains **where tests live**, **how they are wired**, **how to add new ones**, and **how to run them**.

## Layout

| Directory | Role | Default suite (`phpunit.xml`) |
| --------- | ---- | ----------------------------- |
| `tests/Unit` | Fast, isolated tests (no full HTTP stack unless you opt in). | **Yes** — `Unit` |
| `tests/Feature` | HTTP, Inertia, database, auth; uses `Tests\TestCase` + `LazilyRefreshDatabase` (see `tests/Pest.php`). | **Yes** — `Feature` |
| `tests/Browser` | Pest browser plugin + Playwright (`visit()`, real browser). | **No** — run explicitly (see below). |

Configuration:

- **`phpunit.xml`** — test suites (`Unit`, `Feature`), SQLite in-memory defaults, `APP_ENV=testing`, etc.
- **`tests/Pest.php`** — binds `Tests\TestCase` and `LazilyRefreshDatabase` for all **`Feature`** tests; binds `TestCase` only for **`Browser`** (database refresh is opt-in per file).

## Creating a new test

Preferred generator:

```bash
php artisan make:test WorkshopExampleTest --pest
```

- Put **feature** specs under `tests/Feature/` (or a subfolder such as `tests/Feature/Domain/`).
- Put **unit** specs under `tests/Unit/`.
- For **browser** specs, create files under `tests/Browser/` manually or move the generated file there; add `uses(LazilyRefreshDatabase::class);` when the scenario needs a clean database.

### Conventions in this repo

- Use the `test('description', function () { ... })` style to match existing files (not required by Pest, but keeps the tree consistent).
- For **Inertia** responses, use `assertInertia()` with `Inertia\Testing\AssertableInertia as Assert` and assert `component`, props, and counts (see `tests/Feature/WorkshopIndexTest.php`).
- For **Fortify-dependent** behaviour, use `$this->skipUnlessFortifyHas(...)` from `Tests\TestCase` when a feature flag may disable the flow (see `tests/Feature/Settings/SecurityTest.php`).
- Prefer **specific** Laravel assertions (`assertSuccessful()`, `assertRedirect()`, `assertOk()`) over raw status codes where the project already does so.

## Running tests

### Default command (Unit + Feature only)

```bash
php artisan test --compact
```

Or via Composer (also runs **Pint** in `--test` mode first):

```bash
composer run test
```

PHP only (no Pint gate):

```bash
composer run test:php -- --compact
```

With **Sail**:

```bash
./vendor/bin/sail artisan test --compact
```

### Filtering

```bash
php artisan test --compact --filter=WorkshopIndexTest
php artisan test --compact tests/Feature/Domain/WorkshopDomainTest.php
```

### Browser tests (`tests/Browser`)

Not included in the default `phpunit.xml` suites. Run:

```bash
composer run test:browser -- --compact
```

Prerequisites: Node dependencies and Playwright browsers, for example:

```bash
npm install
npx playwright install
```

With Sail (from the app root):

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npx playwright install chromium
./vendor/bin/sail composer run test:browser -- --compact
```

## Database and environment

- **Default:** `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` (see `phpunit.xml`). No MySQL required for the standard suite.
- **Host PHP without `pdo_sqlite`:** tests fail with “could not find driver”. Fix by installing the SQLite PDO extension or running tests **inside Sail**, where extensions are present.
- **MySQL:** override env vars when you need engine-specific behaviour, e.g.:

```bash
DB_CONNECTION=mysql DB_HOST=mysql DB_DATABASE=testing ./vendor/bin/sail artisan test --compact
```

## Inertia and Vite

Feature tests that **render Inertia pages** need a resolvable Vite manifest (or dev server):

- Run **`npm run build`** once, or keep **`npm run dev`** running so `public/hot` / manifest matches current page components.
- If a new Vue page is missing from the built manifest, you may see Vite/manifest errors during `get()` assertions.

## CI-style checks

`composer run ci:check` runs frontend lint/format/types plus `composer run test` (includes Pint `--test` before PHPUnit/Pest).

## Related files

| File | Purpose |
| ---- | ------- |
| `tests/Pest.php` | Pest bootstrapping, `Feature` + `Browser` TestCase binding. |
| `tests/TestCase.php` | Base case; `skipUnlessFortifyHas()` helper. |
| `phpunit.xml` | Suites, in-memory SQLite, testing env vars. |
| `composer.json` scripts | `test`, `test:php`, `test:browser`, `ci:check`. |
