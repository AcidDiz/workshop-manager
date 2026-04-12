# HTTP / web route reference

This folder documents **actual HTTP endpoints** for the workshop-manager application. The stack is **Laravel + session authentication + Inertia**. Responses are typically **HTML with Inertia payloads**, **redirects**, or **validation errors in session**—not a versioned public **JSON REST API**, unless such an API is added explicitly elsewhere.

For architecture and file ownership, see [`../application/README.md`](../application/README.md). For install and operations, see [`../START.md`](../START.md) and the repository [`README.md`](../../README.md).

## What belongs here

| File                                 | Purpose                                                                                     |
| ------------------------------------ | ------------------------------------------------------------------------------------------- |
| [`README.md`](README.md) (this file) | Index and reading order for API-style reference docs.                                       |
| [`web-routes.md`](web-routes.md)     | Web routes registered in `routes/web.php` and `routes/settings.php`, plus notes on Fortify. |

## Reading order

1. [`web-routes.md`](web-routes.md) — Method, URI, name, middleware, typical response, examples.

## Conventions

- All documents in **`docs/api/` are written in English.**
- Prefer **tables** per route group; keep **vendor / internal** routes (e.g. `/up`, `storage`, debug tooling) separate from **application** routes.
- When in doubt, verify with `php artisan route:list` in the app root.
