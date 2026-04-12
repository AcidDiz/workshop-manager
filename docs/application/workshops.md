# Workshop domain (current implementation)

This document describes **what exists in the codebase today**. Features that are only planned (self-service enrolment, admin CRUD, permission middleware on routes) are listed at the end—they must not be read as already shipped.

## Purpose

- Represent **scheduled workshops** with a real **time interval** (`starts_at`, `ends_at`) and a **capacity** integer.
- Represent **enrolments** as a **first-class model** (`WorkshopRegistration`) with an explicit **status** (`confirmed`, `waiting_list`), not an anonymous many-to-many pivot.
- Provide **deterministic demo data** via seeders and a **read-only, authenticated** Inertia page listing **upcoming** workshops.

## Source-of-truth file map

| Path                                                                            | Responsibility                                                                                                               |
| ------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `database/migrations/2026_04_12_094502_create_workshops_table.php`              | Creates `workshops` with interval, `capacity`, `created_by` FK to `users`, index on `starts_at`.                             |
| `database/migrations/2026_04_12_094503_create_workshop_registrations_table.php` | Creates `workshop_registrations` with FKs and **unique** `(workshop_id, user_id)`.                                           |
| `app/Enums/WorkshopRegistrationStatus.php`                                      | Backed enum: `confirmed`, `waiting_list`.                                                                                    |
| `app/Models/Workshop.php`                                                       | `creator()`, `registrations()`; scopes `future()` (`starts_at > now()`), `ordered()` (`orderBy('starts_at')`); `HasFactory`. |
| `app/Models/WorkshopRegistration.php`                                           | `workshop()`, `user()`; scopes `confirmed()`, `waitingList()`; enum cast on `status`; `HasFactory`.                          |
| `app/Models/User.php`                                                           | `createdWorkshops()`, `workshopRegistrations()`.                                                                             |
| `database/factories/WorkshopFactory.php`                                        | Factory with `upcoming()` state for future-dated rows.                                                                       |
| `database/factories/WorkshopRegistrationFactory.php`                            | Factory with `confirmed()` / `waitingList()` states.                                                                         |
| `database/seeders/RolePermissionSeeder.php`                                     | Spatie roles `admin`, `employee`; permissions `workshops.view`, `workshops.manage`; assigns permissions to roles.            |
| `database/seeders/AcademyDemoSeeder.php`                                        | Demo users, three future workshops, three registrations (two confirmed, one waiting list).                                   |
| `database/seeders/DatabaseSeeder.php`                                           | Calls `RolePermissionSeeder` then `AcademyDemoSeeder`.                                                                       |
| `app/Http/Controllers/WorkshopController.php`                                   | `index`: loads `future()->ordered()` workshops, maps props for Inertia.                                                      |
| `resources/js/pages/workshops/Index.vue`                                        | Inertia page: cards for `upcomingWorkshops` (title, description, range, capacity).                                           |
| `resources/js/components/AppSidebar.vue`, `AppHeader.vue`                       | Main nav entry **Workshops** using Wayfinder `workshops` routes.                                                             |

## Design decisions

1. **Interval columns** — `starts_at` and `ends_at` support future overlap checks and reminders without inferring duration from a single timestamp. DB-level `CHECK` constraints for ordering or positive capacity are **not** used in this Laravel schema version; rules are enforced in application code and tests.
2. **Registration as a model** — Supports status, reporting, and waitlists without overloading a pivot table.
3. **Seeded permissions** — `workshops.view` is granted to both `admin` and `employee`; `workshops.manage` to `admin` only. **Route middleware does not yet enforce** `permission:`; policies or middleware should be wired when management UI arrives.

## Implemented user flow

1. User must be **authenticated** and **email verified** (same middleware stack as `dashboard`).
2. `GET /workshops` (`workshops.index`) runs `WorkshopController@index`.
3. Response: Inertia page `workshops/Index` with prop `upcomingWorkshops` (array of serialised workshops with ISO 8601 datetimes).

## Tests (Pest)

For how tests are organised and executed across the app, see [`tests.md`](tests.md).

| Test file                                     | Coverage                                                                           |
| --------------------------------------------- | ---------------------------------------------------------------------------------- |
| `tests/Feature/Domain/WorkshopDomainTest.php` | Eloquent relations, duplicate registration unique constraint, scopes.              |
| `tests/Feature/WorkshopIndexTest.php`         | Guest redirect; authenticated Inertia shape for `workshops/Index`.                 |
| `tests/Feature/AcademyDemoSeederTest.php`     | After `DatabaseSeeder`, roles, users, workshop and registration counts and states. |
| `tests/Feature/SeededWorkshopsPageTest.php`   | After seed, demo workshop titles present in Inertia props for a demo admin user.   |

## Not implemented (planned / out of scope)

- Public **REST JSON API** for workshops (the app is web + session + Inertia).
- **Create / update / delete** workshops from the UI.
- **Self-service enrolment or cancellation** from the UI.
- **`permission:` middleware** (or policies) on `workshops.index` and future management routes.

Roadmap items may appear in workspace `docs/todo/` files if your checkout includes them; those files are not the canonical behaviour spec for this app module.
