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

### Dashboard

Middleware: `auth`, `verified`.

| Method | URI          | Route name  | Typical response     |
| ------ | ------------ | ----------- | -------------------- |
| GET    | `/dashboard` | `dashboard` | Inertia `Dashboard`. |

### Workshops (domain pages)

Canonical detail (parameters, controllers, Inertia props, examples): [`workshops.md`](workshops.md).

Workshops are split into two distinct web areas:

- **App area** (`/app/*`): employee-facing browsing experience.
- **Admin area** (`/admin/*`): management-facing list and table tooling.

#### App workshops index (view permission)

Middleware: `auth`, `verified`, and Laravel `can:viewAny,App\Models\Workshop` (resolved via `WorkshopPolicy`: requires Spatie ability `workshops.view`).

| Method | URI              | Route name           | Typical response                                                                                                                                                                                                                                                                                                                                                                               |
| ------ | ---------------- | -------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/app/workshops` | `app.workshops.index` | **403** if the user cannot `viewAny` workshops. For admins (`workshops.manage`), this URI **redirects** to `admin.workshops.index` to keep the two areas distinct. For employees, returns Inertia `app/workshops/Index` with `workshopList`, `filters`, and employee **card** filters (`employeeFilterFields`). Query: optional `status` (`all` \| `upcoming` \| `closed`), `category_id`, `title`, `starts_on`. |

#### Admin workshops index (manage permission)

Middleware: `auth`, `verified`, and Laravel `can:create,App\Models\Workshop` (resolved via `WorkshopPolicy`: requires Spatie ability `workshops.manage`).

| Method | URI                | Route name             | Typical response                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| ------ | ------------------ | ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/admin/workshops` | `admin.workshops.index` | **403** if the user cannot `create` workshops. Otherwise returns Inertia `admin/workshops/Index` with `workshopList`, `filters`, `showWorkshopTable` (true), and admin **table** metadata (`workshopTableColumns`). Query: optional `status` (`all` \| `upcoming` \| `closed`), `category_id`, `title`, `starts_on`, plus admin-only `created_by`. Admin table sorting: `sort` (`title` \| `starts_at` \| `category.name` \| `creator.name` \| `timing_status`) and `direction` (`asc` \| `desc`). |

**Unauthenticated** access to these URIs redirects to Fortify login (or equivalent) before authorization runs.

Shared Inertia props (via `HandleInertiaRequests`): `auth.workshop_permissions.view` and `auth.workshop_permissions.manage` (booleans) for navigation and conditional CTAs.

### Example: list workshops (default filters for role)

See [`workshops.md`](workshops.md#example-requests).

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
