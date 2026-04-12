# Application documentation

This folder describes **how the workshop-manager application is structured and behaves**: real files, flows, and design choices. It does **not** replace the HTTP endpoint reference—that lives under [`../api/README.md`](../api/README.md).

For installation, running the stack, and quality commands, use the root [`../START.md`](../START.md) (Italian) and [`../../README.md`](../../README.md) (English).

## What belongs here

| Folder / file                        | Purpose                                                                           |
| ------------------------------------ | --------------------------------------------------------------------------------- |
| [`README.md`](README.md) (this file) | Index: reading order and links to all application docs.                           |
| [`workshops.md`](workshops.md)       | Workshop domain: models, seeds, read-only listing UI, tests, and known gaps.      |
| [`tests.md`](tests.md)               | How tests are organised, created, and run (Pest, Feature, Browser, SQLite, Vite). |

## Suggested reading order

1. [`tests.md`](tests.md) — Test layout and commands (useful before changing behaviour covered by tests).
2. [`workshops.md`](workshops.md) — Current workshop and registration model, demo data, and Inertia index page.

## Conventions

- All documents in **`docs/application/` are written in English.**
- Prefer **tables of files and responsibilities** over long narrative when listing ownership.
- Distinguish **implemented** behavior from **planned** work in dedicated sections.
- Link to tests that prove behavior when it helps onboarding.

## Generated and non-authoritative assets

Laravel **Wayfinder** generates TypeScript route helpers under `resources/js/routes` (and related paths). Those files are often **gitignored** and are **not** the source of truth for routes—the Laravel route files and this repo’s `docs/api/` descriptions are. Regenerate after route changes with `php artisan wayfinder:generate` or via `npm run dev` / `npm run build` when the Vite Wayfinder plugin runs.
