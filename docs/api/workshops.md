# Workshops (HTTP / Inertia)

This document describes the **workshop list** endpoints registered in `routes/web.php`. The app is **session + Inertia**: successful browser GETs return **HTML** with an embedded Inertia page payload, not a public JSON REST resource. The examples below show **query strings** and the **Inertia page props** you would see on the client after a successful response.

For domain logic (query pipeline, Eloquent scopes, table metadata), see [`../application/workshops.md`](../application/workshops.md).

## Route registration

Both routes live inside the `auth` + `verified` group in `routes/web.php`:

- **App** — `GET /app/workshops` → `app.workshops.index` → `App\Http\Controllers\App\Workshops\WorkshopIndexController`
- **Admin** — `GET /admin/workshops` → `admin.workshops.index` → `App\Http\Controllers\Admin\Workshops\WorkshopIndexController`

```php
// routes/web.php (excerpt)
Route::middleware(['can:viewAny,'.Workshop::class])
    ->prefix('app')
    ->as('app.')
    ->group(function () {
        Route::get('workshops', AppWorkshopIndexController::class)->name('workshops.index');
    });

Route::middleware(['can:create,'.Workshop::class])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('workshops', AdminWorkshopIndexController::class)->name('workshops.index');
    });
```

| URI                    | Route name              | Middleware (order)                                    | Authorization (policy)                               |
| ---------------------- | ----------------------- | ----------------------------------------------------- | ---------------------------------------------------- |
| `GET /app/workshops`   | `app.workshops.index`   | `auth`, `verified`, `can:viewAny,App\Models\Workshop` | `WorkshopPolicy::viewAny` → Spatie `workshops.view`  |
| `GET /admin/workshops` | `admin.workshops.index` | `auth`, `verified`, `can:create,App\Models\Workshop`  | `WorkshopPolicy::create` → Spatie `workshops.manage` |

Unauthenticated users are redirected to Fortify login before these middleware run.

## Request handling (`ListWorkshopsIndexRequest`)

Both controllers type-hint `ListWorkshopsIndexRequest`. It:

- **Authorizes** only if the user can `viewAny` on `Workshop` (i.e. has `workshops.view`). If authorization fails, the response is **403**.
- **Validates** query parameters. Rules for `created_by`, `sort`, and `direction` are **removed** for users who cannot `workshops.manage` (employees never send validatable admin-only params).
- Normalises empty query values for `status`, `sort`, and `direction` to `null` when the key is present but blank.

### Query parameters

| Parameter     | Type / values                                                          | App (`/app/workshops`)             | Admin (`/admin/workshops`) |
| ------------- | ---------------------------------------------------------------------- | ---------------------------------- | -------------------------- |
| `status`      | `all` \| `upcoming` \| `closed` or omitted                             | Allowed                            | Allowed                    |
| `category_id` | integer, exists in `workshop_categories`                               | Allowed                            | Allowed                    |
| `title`       | string, max 255                                                        | Allowed                            | Allowed                    |
| `starts_on`   | date                                                                   | Allowed                            | Allowed                    |
| `created_by`  | integer, exists in `users`                                             | **Not in rules** (ignored if sent) | Allowed                    |
| `sort`        | `title`, `category.name`, `starts_at`, `creator.name`, `timing_status` | **Not in rules**                   | Allowed                    |
| `direction`   | `asc` \| `desc`                                                        | **Not in rules**                   | Allowed                    |

**Effective default for `status`:** when `status` is omitted, `App\Support\Filters\Workshops\BuildWorkshopIndexData` applies `upcoming` for users **without** `workshops.manage` and `all` for users **with** `workshops.manage`. The `filters.status` prop echoed to the UI remains `null` until the user selects a value.

Invalid query values result in a normal Laravel validation response (typically **redirect back** with session errors for a full-page GET).

## Controllers

### `App\Http\Controllers\App\Workshops\WorkshopIndexController`

- Resolves the authenticated user (must be non-null under `auth`).
- If the user **`can('workshops.manage')`**, returns **`302`** to `route('admin.workshops.index', $request->query())` so admins always land on the admin Inertia page with the same query string.
- Otherwise calls `BuildWorkshopIndexData::handle($user, $request->validated())` and renders Inertia page **`app/workshops/Index`**.

### `App\Http\Controllers\Admin\Workshops\WorkshopIndexController`

- Calls `BuildWorkshopIndexData::handle($user, $request->validated())` and renders Inertia page **`admin/workshops/Index`**.
- No redirect; only users who pass `can:create,Workshop` reach this action.

Both controllers pass the same **prop keys**; values differ by role (table vs cards, column metadata, etc.).

## Inertia page props (successful GET)

Shared props from `HandleInertiaRequests` (relevant subset):

- `auth.user` — current user or `null`
- `auth.workshop_permissions.view` — boolean
- `auth.workshop_permissions.manage` — boolean

Page-specific props from the workshop index controllers:

| Prop                   | Type      | Notes                                                                                                                          |
| ---------------------- | --------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `workshopList`         | `array`   | Resolved `WorkshopListItemResource` collection (no wrapper array; see `AppServiceProvider` `JsonResource::withoutWrapping()`). |
| `filters`              | `object`  | Echo of active / requested filters (see below).                                                                                |
| `showWorkshopTable`    | `boolean` | `true` only when `workshops.manage`.                                                                                           |
| `workshopTableColumns` | `array`   | Non-empty for admin; empty array for employee.                                                                                 |
| `employeeFilterFields` | `array`   | Non-empty for employee; empty array for admin.                                                                                 |

### `filters` shape

Keys always present; values may be `null`.

```json
{
    "status": null,
    "category_id": null,
    "title": null,
    "starts_on": null,
    "created_by": null,
    "sort": null,
    "direction": null
}
```

- For employees, `created_by`, `sort`, and `direction` stay `null`.
- `status` is the **requested** query value, not the internal default (`upcoming` / `all`).

### `workshopList` item shape (`WorkshopListItemResource`)

Each element is a plain object:

```json
{
    "id": 1,
    "title": "Laravel in practice",
    "description": "…",
    "starts_at": "2026-04-20T10:00:00+00:00",
    "ends_at": "2026-04-20T14:00:00+00:00",
    "capacity": 20,
    "category": { "id": 1, "name": "Laravel backend" },
    "creator": { "id": 2, "name": "Academy Admin" },
    "timing_status": "upcoming"
}
```

`timing_status` is `upcoming` when `starts_at` is in the future, otherwise `closed`. Placeholder `category` / `creator` objects use `id: null` and `name: "—"` when the relation is missing.

## Example requests

### Employee — default listing (no query)

Effective filter: upcoming-only on the server; UI may show empty `filters.status`.

```http
GET /app/workshops HTTP/1.1
Host: example.test
Cookie: <session>
Accept: text/html
```

**Typical success:** `200 OK`, Inertia component `app/workshops/Index`, props as above with `showWorkshopTable: false`, `employeeFilterFields` populated.

### Employee — filtered

```http
GET /app/workshops?status=all&category_id=3&starts_on=2026-04-15 HTTP/1.1
Host: example.test
Cookie: <session>
Accept: text/html
```

### Admin — redirected from app URL

```http
GET /app/workshops?sort=title&direction=asc HTTP/1.1
Host: example.test
Cookie: <session>
Accept: text/html
```

**Typical success:** `302` to `/admin/workshops?sort=title&direction=asc`.

### Admin — table + sort

```http
GET /admin/workshops?status=closed&sort=starts_at&direction=desc&created_by=5 HTTP/1.1
Host: example.test
Cookie: <session>
Accept: text/html
```

**Typical success:** `200 OK`, Inertia component `admin/workshops/Index`, `showWorkshopTable: true`, `workshopTableColumns` non-empty.

## Failure cases (summary)

| Condition                                                      | Typical response                                                             |
| -------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| Guest                                                          | Redirect to login (Fortify).                                                 |
| Authenticated but cannot `viewAny` workshop                    | **403** on both URIs (FormRequest `authorize` fails before controller body). |
| Employee hitting `/admin/workshops` without `workshops.manage` | **403** from `can:create` middleware.                                        |
| Invalid query parameters                                       | Validation error (redirect with session errors or equivalent).               |

## Related files

| File                                                               | Role                               |
| ------------------------------------------------------------------ | ---------------------------------- |
| `routes/web.php`                                                   | Registers prefixes and middleware. |
| `app/Http/Controllers/App/Workshops/WorkshopIndexController.php`   | App entry + admin redirect.        |
| `app/Http/Controllers/Admin/Workshops/WorkshopIndexController.php` | Admin entry.                       |
| `app/Http/Requests/Workshops/ListWorkshopsIndexRequest.php`        | Authorization + query validation.  |
| `app/Support/Filters/Workshops/BuildWorkshopIndexData.php`         | Query + metadata for both pages.   |
| `app/Http/Resources/Workshop/WorkshopListItemResource.php`         | `workshopList` row shape.          |
