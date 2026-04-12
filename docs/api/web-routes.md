# Web routes

Unless noted, the app expects **browser** requests with **session** cookies. **Inertia** pages return HTML that boots the client bundle; **redirects** follow standard Laravel behaviour.

## Authentication (Laravel Fortify)

Login, logout, registration, password reset, email verification, and two-factor challenge routes are registered by **Fortify**, not `routes/web.php`. They are **session-based** and may return redirects, Inertia auth pages, or validation errors in session.

Discover names and paths with:

```bash
php artisan route:list
```

Filter with `grep` if needed, for example:

```bash
php artisan route:list | grep -i login
```

Typical patterns:

- **Guest-only** auth pages redirect authenticated users away.
- **POST** auth actions validate input; failures redirect back with errors.
- **Verified** middleware on app routes may redirect to the verification notice when email is unverified.

## Public and marketing

| Method | URI | Route name | Auth  | Typical response                                                                  |
| ------ | --- | ---------- | ----- | --------------------------------------------------------------------------------- |
| GET    | `/` | `home`     | Guest | Inertia `Welcome`; prop `canRegister` reflects Fortify registration feature flag. |

## Authenticated application (`routes/web.php`)

Middleware on the group: `auth`, `verified` (email must be verified).

| Method | URI          | Route name        | Typical response                                                                                                  |
| ------ | ------------ | ----------------- | ----------------------------------------------------------------------------------------------------------------- |
| GET    | `/dashboard` | `dashboard`       | Inertia `Dashboard`.                                                                                              |
| GET    | `/workshops` | `workshops.index` | Inertia `workshops/Index`; prop `upcomingWorkshops` (workshops with `starts_at > now()`, ordered by `starts_at`). |

**Unauthenticated** access to these URIs redirects to Fortify login (or equivalent).

### Example: list upcoming workshops

```http
GET /workshops HTTP/1.1
Host: <your-host>
Cookie: laravel_session=...
Accept: text/html
```

**Typical success:** `200 OK` with HTML shell and Inertia page component `workshops/Index`.

## Settings (`routes/settings.php`)

Routes are split by middleware:

### `auth` only (verification not required)

| Method | URI                 | Route name       | Notes                                                         |
| ------ | ------------------- | ---------------- | ------------------------------------------------------------- |
| GET    | `/settings`         | —                | Redirects to `/settings/profile`.                             |
| GET    | `/settings/profile` | `profile.edit`   | Inertia profile editor.                                       |
| PATCH  | `/settings/profile` | `profile.update` | Validates profile fields; redirect back or to `profile.edit`. |

### `auth` + `verified`

| Method | URI                       | Route name             | Notes                                   |
| ------ | ------------------------- | ---------------------- | --------------------------------------- |
| DELETE | `/settings/profile`       | `profile.destroy`      | Account deletion flow.                  |
| GET    | `/settings/security`      | `security.edit`        | Security / 2FA settings (when enabled). |
| PUT    | `/settings/user-password` | `user-password.update` | Password update; **throttle** `6,1`.    |
| GET    | `/settings/appearance`    | `appearance.edit`      | Inertia appearance preferences.         |

Typical **failure** responses: `403` when policy or feature disallows action; validation redirect with session errors for form posts.

## Internal, health, and vendor routes

| Area             | Example             | Note                                                                                         |
| ---------------- | ------------------- | -------------------------------------------------------------------------------------------- |
| Health           | `GET /up`           | Laravel health check; not part of workshop domain docs.                                      |
| Storage          | Fortify / framework | `storage` link and similar; see `route:list`.                                                |
| Debug / packages | e.g. Debugbar       | If installed, routes appear in `route:list`; keep them out of business-domain documentation. |

Do **not** document these as if they were product features unless the team explicitly treats them as such.
