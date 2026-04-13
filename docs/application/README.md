# Application documentation

This folder describes **how the workshop-manager application is structured and behaves**: real files, flows, and design choices. It does **not** replace the HTTP endpoint reference—that lives under [`../api/README.md`](../api/README.md).

For installation, running the stack, and quality commands, use the root [`../START.md`](../START.md) (Italian) and [`../../README.md`](../../README.md) (English).

## What belongs here

| Folder / file                        | Purpose                                                                           |
| ------------------------------------ | --------------------------------------------------------------------------------- |
| [`README.md`](README.md) (this file) | Index: reading order and links to all application docs.                           |
| [`workshops.md`](workshops.md)       | Workshop domain: models, filter/sort scope traits, `BuildWorkshopIndexData`, table column metadata, read-only listing UI, tests, known gaps. |
| [`tests.md`](tests.md)               | How tests are organised, created, and run (Pest, Feature, Browser, SQLite, Vite). |

## Suggested reading order

1. [`tests.md`](tests.md) — Test layout and commands (useful before changing behaviour covered by tests).
2. [`workshops.md`](workshops.md) — Workshop model, query scopes, index pipeline, demo data, and Inertia app/admin index pages.

## Conventions

- All documents in **`docs/application/` are written in English.**
- Prefer **tables of files and responsibilities** over long narrative when listing ownership.
- Distinguish **implemented** behavior from **planned** work in dedicated sections.
- Link to tests that prove behavior when it helps onboarding.

## Generated and non-authoritative assets

Laravel **Wayfinder** provides generated TypeScript route helpers that the frontend imports as `@/routes/*` (for example `@/routes/app/workshops` and `@/routes/admin/workshops`). Those helpers may be **generated at build/dev time** by the Vite Wayfinder plugin and can be **gitignored** (so they might not exist as committed source files on disk). They are **not** the source of truth for routes—the Laravel route files and this repo’s `docs/api/` descriptions are. Regenerate after route changes with `php artisan wayfinder:generate` or via `npm run dev` / `npm run build` when the Vite Wayfinder plugin runs.
