# Workshops (HTTP / Inertia)

This document describes the **workshop list** endpoints registered in `routes/web.php`. The app is **session + Inertia**: successful browser GETs return **HTML** with an embedded Inertia page payload, not a public JSON REST resource. The examples below show **query strings** and the **Inertia page props** you would see on the client after a successful response.

For domain logic (query pipeline, Eloquent scopes, table metadata), see [`../application/workshops.md`](../application/workshops.md).

## Route registration

Both routes live inside the `auth` + `verified` group in `routes/web.php`:

- **App** — `GET /app/dashboard` → `app.dashboard` → `App\Http\Controllers\App\Dashboard\DashboardIndexController`
- **App** — `GET /app/workshops` → `app.workshops.index` → `App\Http\Controllers\App\Workshops\WorkshopIndexController`
- **Admin** — `GET /admin/workshops` → `admin.workshops.index` → `App\Http\Controllers\Admin\Workshops\WorkshopIndexController`

```php
// routes/web.php (excerpt; imports include AdminDashboardController, AppDashboardIndexController, WorkshopShowController, WorkshopNextDayRemindDispatchController, WorkshopRemindDispatchController, WorkshopParticipantAttachController, WorkshopParticipantDetachController, WorkshopRegistrationAttachController / WorkshopRegistrationDetachController)
Route::middleware(['can:viewAny,'.Workshop::class])
    ->prefix('app')
    ->as('app.')
    ->group(function () {
        Route::get('dashboard', AppDashboardIndexController::class)->name('dashboard');
        Route::get('workshops', AppWorkshopIndexController::class)->name('workshops.index');

        Route::post('workshops/{workshop}/registrations', WorkshopRegistrationAttachController::class)
            ->middleware(['can:attachRegistration,workshop'])
            ->name('workshops.registrations.attach');

        Route::delete('workshops/{workshop}/registrations', WorkshopRegistrationDetachController::class)
            ->middleware(['can:detachRegistration,workshop'])
            ->name('workshops.registrations.detach');
    });

Route::prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::middleware(['can:create,'.Workshop::class])->group(function () {
            Route::get('dashboard', AdminDashboardController::class)->name('dashboard');
            Route::get('workshops', AdminWorkshopIndexController::class)->name('workshops.index');
            Route::get('workshops/create', WorkshopCreateController::class)->name('workshops.create');
            Route::post('workshops', WorkshopStoreController::class)->name('workshops.store');
            Route::post('workshops/reminders/next-day', WorkshopNextDayRemindDispatchController::class)
                ->name('workshops.reminders.next-day');
        });

        Route::get('workshops/{workshop}', WorkshopShowController::class)
            ->middleware(['can:update,workshop'])
            ->name('workshops.show');

        Route::post('workshops/{workshop}/participants', WorkshopParticipantAttachController::class)
            ->middleware(['can:update,workshop'])
            ->name('workshops.participants.attach');

        Route::post('workshops/{workshop}/reminders', WorkshopRemindDispatchController::class)
            ->middleware(['can:update,workshop'])
            ->name('workshops.reminders.dispatch');

        Route::delete('workshops/{workshop}/participants', WorkshopParticipantDetachController::class)
            ->middleware(['can:update,workshop'])
            ->name('workshops.participants.detach');

        Route::get('workshops/{workshop}/edit', WorkshopEditController::class)
            ->middleware(['can:update,workshop'])
            ->name('workshops.edit');

        Route::put('workshops/{workshop}', WorkshopUpdateController::class)
            ->middleware(['can:update,workshop'])
            ->name('workshops.update');

        Route::delete('workshops/{workshop}', WorkshopDestroyController::class)
            ->middleware(['can:delete,workshop'])
            ->name('workshops.destroy');
    });
```

| URI                                              | Route name                           | Middleware (order)                                                                       | Authorization (policy)                                                                                                                                                                 |
| ------------------------------------------------ | ------------------------------------ | ---------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /app/dashboard`                             | `app.dashboard`                      | `auth`, `verified`, `can:viewAny,App\Models\Workshop`                                    | `WorkshopPolicy::viewAny` → Spatie `workshops.view`; Inertia `app/dashboard/Index`, props **`registrationSummary`**, **`upcomingRegistrations`**, **`completedWorkshops`**. |
| `GET /app/workshops`                             | `app.workshops.index`                | `auth`, `verified`, `can:viewAny,App\Models\Workshop`                                    | `WorkshopPolicy::viewAny` → Spatie `workshops.view`                                                                                                                                    |
| `POST /app/workshops/{workshop}/registrations`   | `app.workshops.registrations.attach` | `auth`, `verified`, `can:viewAny,App\Models\Workshop`, `can:attachRegistration,workshop` | `WorkshopPolicy::attachRegistration` → same as `view`                                                                                                                                  |
| `DELETE /app/workshops/{workshop}/registrations` | `app.workshops.registrations.detach` | `auth`, `verified`, `can:viewAny,App\Models\Workshop`, `can:detachRegistration,workshop` | `WorkshopPolicy::detachRegistration` → same as `view`                                                                                                                                  |
| `GET /admin/workshops`                           | `admin.workshops.index`              | `auth`, `verified`, `can:create,App\Models\Workshop`                                     | `WorkshopPolicy::create` → Spatie `workshops.manage`                                                                                                                                   |
| `GET /admin/dashboard`                           | `admin.dashboard`                    | `auth`, `verified`, `can:create,App\Models\Workshop`                                     | `create` → `workshops.manage`; Inertia `admin/dashboard/Index`, prop `statistics` with aggregate cards plus optional **`next_upcoming_workshop`**; live updates via **Echo** private channel `admin.workshop-statistics` / event `statistics.updated`. |

Additional **admin workshop management** routes (same `auth` + `verified` prefix; see `routes/web.php`):

| Method   | URI                                        | Route name                            | Middleware (extra)               | Policy / ability              |
| -------- | ------------------------------------------ | ------------------------------------- | -------------------------------- | ----------------------------- |
| `GET`    | `/admin/workshops/create`                  | `admin.workshops.create`              | `can:create,App\Models\Workshop` | `create` → `workshops.manage` |
| `POST`   | `/admin/workshops`                         | `admin.workshops.store`               | `can:create,App\Models\Workshop` | `create` → `workshops.manage` |
| `POST`   | `/admin/workshops/reminders/next-day`      | `admin.workshops.reminders.next-day`  | `can:create,App\Models\Workshop` | `create` → `workshops.manage` |
| `GET`    | `/admin/workshops/{workshop}`              | `admin.workshops.show`                | `can:update,workshop`            | `update` → `workshops.manage` |
| `POST`   | `/admin/workshops/{workshop}/participants` | `admin.workshops.participants.attach` | `can:update,workshop`            | `update` → `workshops.manage` |
| `POST`   | `/admin/workshops/{workshop}/reminders`    | `admin.workshops.reminders.dispatch`  | `can:update,workshop`            | `update` → `workshops.manage` |
| `DELETE` | `/admin/workshops/{workshop}/participants` | `admin.workshops.participants.detach` | `can:update,workshop`            | `update` → `workshops.manage` |
| `GET`    | `/admin/workshops/{workshop}/edit`         | `admin.workshops.edit`                | `can:update,workshop`            | `update` → `workshops.manage` |
| `PUT`    | `/admin/workshops/{workshop}`              | `admin.workshops.update`              | `can:update,workshop`            | `update` → `workshops.manage` |
| `DELETE` | `/admin/workshops/{workshop}`              | `admin.workshops.destroy`             | `can:delete,workshop`            | `delete` → `workshops.manage` |

`{workshop}` is route-model bound to `App\Models\Workshop`.

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

**Effective default for `status`:** when `status` is omitted, `WorkshopUserFilters` applies `upcoming` on **`GET /app/workshops`** and `WorkshopAdminFilters` applies `all` on **`GET /admin/workshops`**. The `filters.status` prop echoed to the UI remains `null` until the user selects a value.

Invalid query values result in a normal Laravel validation response (typically **redirect back** with session errors for a full-page GET).

## Controllers

### `App\Http\Controllers\App\Workshops\WorkshopIndexController`

- Resolves the authenticated user (must be non-null under `auth`).
- If the user **`can('workshops.manage')`**, returns **`302`** to `route('admin.workshops.index', $request->query())` so admins always land on the admin Inertia page with the same query string.
- Otherwise calls `WorkshopUserFilters::index($request->validated())` and renders Inertia page **`app/workshops/Index`** with **`workshopList`**, **`filters`**, **`cardFilterFields`** (no table column props). Each `workshopList` row includes **`my_registration_status`** (`confirmed`, `waiting_list`, or `null`) for the current user.

### `App\Http\Controllers\App\Workshops\WorkshopRegistrationAttachController`

- **`POST /app/workshops/{workshop}/registrations`** (`app.workshops.registrations.attach`).
- Authorizes `attachRegistration` on the bound workshop.
- Delegates to **`App\Services\Workshop\WorkshopRegistrationService::attach`**: requires `starts_at` in the future, no existing row for `(workshop, user)`, **no time overlap** with any other workshop registration for that user, then creates **`confirmed`** if confirmed count is below `capacity`, otherwise **`waiting_list`**.
- **`302`** back to the previous URL (typically the app index) with **`Inertia::flash('toast', …)`** (`success` with message depending on `confirmed` vs `waiting_list`, or `error` for domain failures such as overlap / duplicate / closed workshop).

### `App\Http\Controllers\App\Workshops\WorkshopRegistrationDetachController`

- **`DELETE /app/workshops/{workshop}/registrations`** (`app.workshops.registrations.detach`; Inertia forms use `_method=DELETE`).
- Authorizes `detachRegistration` on the bound workshop.
- Delegates to **`App\Services\Workshop\WorkshopCancellationService::detach`**: deletes the current user’s registration if present (**idempotent** if already absent). Cancelling a **`confirmed`** seat may promote one **`waiting_list`** row to **`confirmed`** in the same transaction (FIFO by `created_at`, `id`), via private logic on the cancellation service.
- **`302`** back with **`Inertia::flash('toast', …)`** (`success` when removed — message differs for waiting list vs confirmed — or `info` when the user was not registered).

### `App\Http\Controllers\Admin\Workshops\WorkshopIndexController`

- Calls `WorkshopAdminFilters::index($request->validated())` and renders Inertia page **`admin/workshops/Index`** with **`workshopList`**, **`filters`**, **`workshopTableColumns`** (no card filter props).
- No redirect; only users who pass `can:create,Workshop` reach this action.

Index prop keys are **split by route**: the app page never receives `workshopTableColumns`; the admin page never receives `cardFilterFields`.

### Admin CRUD (invokable controllers under `Admin\Workshops\`)

| Controller                                | Route                                            | Typical success                                                                                                                                                                                                                                                                                                                                                                                     |
| ----------------------------------------- | ------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `WorkshopCreateController`                | `GET admin/workshops/create`                     | `200`, Inertia `admin/workshops/Create`, props: `categories` (`id`, `name`).                                                                                                                                                                                                                                                                                                                        |
| `WorkshopStoreController`                 | `POST admin/workshops`                           | `302` to `admin.workshops.index`, `Inertia::flash('toast', …)` success message. Body: see **Store / update body** below.                                                                                                                                                                                                                                                                            |
| `WorkshopNextDayRemindDispatchController` | `POST admin/workshops/reminders/next-day`        | `302` back (typically admin index). Calls **`WorkshopReminderService::dispatchNextCalendarDayReminders`** (same selection as `workshops:remind`). Flash **`toast`**: `success` with sent count, or **`info`** if zero emails.                                                                                                                                                                       |
| `WorkshopShowController`                  | `GET admin/workshops/{workshop}`                 | `200`, Inertia `admin/workshops/Show`, props: `workshop` (`WorkshopShowResource`), `canAttachParticipants`, `participantList`, `assignableUsers` (employees not yet registered for this workshop), `participantTableColumns` (includes **`_actions`**), `filters` (`sort`/`direction` always `null`).                                                                                              |
| `WorkshopParticipantAttachController`     | `POST admin/workshops/{workshop}/participants`   | `302` back with flash toast. Body: `user_id` (required, must be a user with **`employee`** role). Uses **`WorkshopRegistrationService::attachAsAdmin`**: skips the self-service “workshop must be upcoming” rule; still enforces duplicate registration and schedule overlap for that user; **requires a free confirmed seat** (fails when `confirmed` ≥ `capacity`; no admin-driven waiting list). |
| `WorkshopRemindDispatchController`        | `POST admin/workshops/{workshop}/reminders`      | `302` back. Sends **`WorkshopReminderNotification`** to every **confirmed** registration for this workshop only if **`starts_at` is still in the future**; otherwise flash **`error`**. If no confirmed rows, flash **`info`**.                                                                                                                                                                     |
| `WorkshopParticipantDetachController`     | `DELETE admin/workshops/{workshop}/participants` | `302` back with flash toast. Body: `user_id`. Delegates to **`WorkshopCancellationService::detach`** (same FIFO promotion behaviour as app self-detach when a **confirmed** seat is freed).                                                                                                                                                                                                         |
| `WorkshopEditController`                  | `GET admin/workshops/{workshop}/edit`            | `200`, Inertia `admin/workshops/Edit`, props: `categories`, `workshop` (form payload from `WorkshopFormResource`).                                                                                                                                                                                                                                                                                  |
| `WorkshopUpdateController`                | `PUT admin/workshops/{workshop}`                 | Same redirect + flash as store. Body: same fields as store.                                                                                                                                                                                                                                                                                                                                         |
| `WorkshopDestroyController`               | `DELETE admin/workshops/{workshop}`              | Same redirect + flash. **DB cascade** removes related `workshop_registrations`.                                                                                                                                                                                                                                                                                                                     |

**Store / update body** (form / `multipart` or `x-www-form-urlencoded`; validated by `StoreWorkshopRequest` / `UpdateWorkshopRequest`):

| Field                  | Rules (summary)                                                                       |
| ---------------------- | ------------------------------------------------------------------------------------- |
| `title`                | required, string, max 255                                                             |
| `description`          | optional string                                                                       |
| `workshop_category_id` | optional, integer, exists in `workshop_categories`; empty string normalised to `null` |
| `starts_at`            | required, valid date (HTML `datetime-local` accepted)                                 |
| `ends_at`              | required, date, must be **after** `starts_at`                                         |
| `capacity`             | required, integer, 1–100000; on **update**, cannot be lower than the current confirmed participant count |

Validation failures: **redirect back** with session errors (Inertia will re-render the previous page with `errors`).

## Inertia page props (successful GET)

Shared props from `HandleInertiaRequests` (relevant subset):

- `auth.user` — current user or `null`
- `auth.workshop_permissions.view` — boolean
- `auth.workshop_permissions.manage` — boolean

Page-specific props from the workshop index controllers:

| Prop                      | Type     | Present on           | Notes                                                                                                                                        |
| ------------------------- | -------- | -------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| `workshopList`            | `array`  | app + admin          | Resolved `WorkshopListItemResource` collection (no wrapper array; see `AppServiceProvider` `JsonResource::withoutWrapping()`).               |
| `filters`                 | `object` | app + admin          | Echo of active / requested filters (see below).                                                                                              |
| `cardFilterFields`        | `array`  | **app** index only   | Filter bar field defs for the card UI (`param`, `label`, `input_type`, optional `options`).                                                  |
| `workshopTableColumns`    | `array`  | **admin** index only | Non-empty; includes a final **`_actions`** column with `cast_type` `actions`.                                                                |
| `canAttachParticipants`   | `bool`   | **admin** show only  | `true` when confirmed registrations are still below `capacity`; used to hide / disable attach UI when the workshop is already full.           |
| `participantList`         | `array`  | **admin** show only  | Plain objects from `WorkshopParticipantRowResource` (confirmed rows before waiting list).                                                    |
| `assignableUsers`         | `array`  | **admin** show only  | `{ id, name, email }` for **`employee`** users not already registered on this workshop.                                                      |
| `participantTableColumns` | `array`  | **admin** show only  | From `WorkshopRegistrationTableColumns::adminShowTable()`; includes **`_actions`**; status uses a page-level `Table` slot for badge styling. |

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
    "confirmed_registrations_count": 3,
    "enrollment": "3/20",
    "category": { "id": 1, "name": "Laravel backend" },
    "creator": { "id": 2, "name": "Academy Admin" },
    "timing_status": "upcoming",
    "timing_status_badge_class": "border-transparent bg-emerald-500/15 text-emerald-700 dark:text-emerald-400",
    "my_registration_status": null
}
```

On **`GET /app/workshops`**, `my_registration_status` reflects the signed-in user’s row for that workshop (`confirmed`, `waiting_list`, or `null`). On **`GET /admin/workshops`**, the admin list does not resolve per-viewer enrolment; this field is **`null`** for every row.

`confirmed_registrations_count` counts **confirmed** registrations only. `timing_status` is `upcoming` when `starts_at` is in the future, otherwise `closed`; `timing_status_badge_class` is produced by `WorkshopStatusEnum::badgeClassName()` for the UI badge. Placeholder `category` / `creator` objects use `id: null` and `name: "—"` when the relation is missing.

## Example requests

### Employee — default listing (no query)

Effective filter: upcoming-only on the server; UI may show empty `filters.status`.

```http
GET /app/workshops HTTP/1.1
Host: example.test
Cookie: <session>
Accept: text/html
```

**Typical success:** `200 OK`, Inertia component `app/workshops/Index`, props: `workshopList`, `filters`, `cardFilterFields` (no `workshopTableColumns`).

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

**Typical success:** `200 OK`, Inertia component `admin/workshops/Index`, props: `workshopList`, `filters`, `workshopTableColumns` non-empty (no `cardFilterFields`).

## Failure cases (summary)

| Condition                                                                                              | Typical response                                                             |
| ------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------- |
| Guest                                                                                                  | Redirect to login (Fortify).                                                 |
| Authenticated but cannot `viewAny` workshop                                                            | **403** on both URIs (FormRequest `authorize` fails before controller body). |
| Employee hitting `/admin/workshops` (or any admin workshop show / CRUD URI) without `workshops.manage` | **403** from `can:*` middleware.                                             |
| Invalid query parameters (index GET)                                                                   | Validation error (redirect with session errors or equivalent).               |
| Invalid POST/PUT body (create / update)                                                                | Redirect back with session validation errors.                                |

## Related files

| File                                                                               | Role                                         |
| ---------------------------------------------------------------------------------- | -------------------------------------------- |
| `routes/web.php`                                                                   | Registers prefixes and middleware.           |
| `app/Http/Controllers/App/Workshops/WorkshopIndexController.php`                   | App entry + admin redirect.                  |
| `app/Http/Controllers/Admin/Workshops/WorkshopIndexController.php`                 | Admin index.                                 |
| `app/Http/Controllers/Admin/Workshops/WorkshopCreateController.php`                | Admin create form.                           |
| `app/Http/Controllers/Admin/Workshops/WorkshopStoreController.php`                 | Admin create persist.                        |
| `app/Http/Controllers/Admin/Workshops/WorkshopShowController.php`                  | Admin workshop detail + participants table.  |
| `app/Http/Controllers/Admin/Workshops/WorkshopNextDayRemindDispatchController.php` | Admin UI: bulk “tomorrow” reminder dispatch. |
| `app/Http/Controllers/Admin/Workshops/WorkshopRemindDispatchController.php`        | Admin UI: single-workshop reminder dispatch. |
| `app/Http/Controllers/Admin/Workshops/WorkshopParticipantAttachController.php`     | Admin add employee to workshop.              |
| `app/Http/Controllers/Admin/Workshops/WorkshopParticipantDetachController.php`     | Admin remove user registration.              |
| `app/Http/Requests/Workshops/AttachWorkshopParticipantRequest.php`                 | Validates `user_id` (employee).              |
| `app/Http/Requests/Workshops/DetachWorkshopParticipantRequest.php`                 | Validates `user_id`.                         |
| `app/Http/Controllers/Admin/Workshops/WorkshopEditController.php`                  | Admin edit form.                             |
| `app/Http/Controllers/Admin/Workshops/WorkshopUpdateController.php`                | Admin update persist.                        |
| `app/Http/Controllers/Admin/Workshops/WorkshopDestroyController.php`               | Admin delete.                                |
| `app/Http/Requests/Workshops/ListWorkshopsIndexRequest.php`                        | Authorization + query validation.            |
| `app/Http/Requests/Workshops/StoreWorkshopRequest.php`                             | Admin create validation.                     |
| `app/Http/Requests/Workshops/UpdateWorkshopRequest.php`                            | Admin update validation.                     |
| `app/Support/Filters/Workshops/WorkshopUserFilters.php`                            | Non-admin index query + `cardFilterFields`.  |
| `app/Support/Filters/Workshops/WorkshopAdminFilters.php`                           | Admin index query + `workshopTableColumns`.  |
| `app/Http/Resources/Workshop/WorkshopListItemResource.php`                         | `workshopList` row shape.                    |
| `app/Http/Resources/Workshop/WorkshopShowResource.php`                             | Admin show `workshop` prop.                  |
| `app/Http/Resources/Workshop/WorkshopParticipantRowResource.php`                   | Admin show `participantList` rows.           |
| `app/Support/Tables/WorkshopRegistrationTableColumns.php`                          | Admin show participant table columns.        |
