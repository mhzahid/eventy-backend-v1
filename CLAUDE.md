# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Eventy is a Laravel 13 API backend for an event ticketing platform. Organizers create events and sell tickets; users purchase them. ACL is handled via roles (`super-admin`, `organizer`, `user`).

## Commands

```bash
php artisan test                          # run full test suite
php artisan test --filter TestClassName   # run a single test class
php artisan migrate                       # run pending migrations
php artisan migrate:rollback              # roll back last batch
php artisan stats:backfill                # backfill organizer_stats_* tables from historical orders
php artisan schedule:run                  # run scheduled tasks (cron every minute)
php artisan queue:work                    # process queued jobs (run via Supervisor in production)
./vendor/bin/pint                         # format code (Laravel Pint / PSR-12)
```

## Architecture

### Auth & Roles

- Authentication: Laravel Sanctum (Bearer tokens)
- Authorization: Spatie Laravel Permission — roles are `super-admin`, `organizer`, `user`
- `super-admin` bypasses all permission checks via `Gate::before` in `AppServiceProvider`
- Route middleware chain for organizer routes: `auth:sanctum` → `role:organizer`

### Route Layout (`routes/api.php`)

```
/auth/otp/*            OTP login for users
/auth/organizer/*      Organizer register + login
/v1/countries          Public reference data
/v1/orders             Public checkout (no auth)
/v1/payment/*          Payment callbacks and IPN (no auth, called by gateways)
/v1/user               Sanctum-authenticated, any role
/v1/organizer/*        Sanctum-authenticated + organizer role only
```

Use `Route::controller(Foo::class)->group(...)` when multiple routes share a controller.

### Service Layer

Every non-trivial operation lives in a service class in `app/Services/`. Controllers handle HTTP concerns (file uploads, cache flush, response format) and delegate business logic to services. Services are constructor-injected into controllers.

Key services:

- `EventService` — event CRUD including ticket/participant/form sub-resources, file deletion on update
- `OrganizerService` — profile update (also writes `users.phone`), owner info upsert, banking create
- `OrganizerStatsService` — atomic `INSERT … ON DUPLICATE KEY UPDATE` increments into stats tables; `recompute()` for reconciliation
- `OrderConfirmationService` — marks order paid, dispatches `OrderPaid` event
- `PaymentService` / `BkashGateway` / `SslcommerzGateway` — payment gateway abstraction

### Event-Driven Stats

Order payment → `OrderPaid` event dispatched inside `OrderConfirmationService` → `IncrementOrganizerStats` listener (implements `ShouldQueue`, `$afterCommit = true`) → `OrganizerStatsService::increment()` writes to three pre-aggregated tables:

| Table                     | Granularity                  |
| ------------------------- | ---------------------------- |
| `organizer_stats_daily`   | per organizer per date       |
| `organizer_stats_monthly` | per organizer per year+month |
| `organizer_stats_yearly`  | per organizer per year       |

The `chart` API endpoint reads from these tables (max ~19 rows) rather than aggregating millions of order rows at query time. A nightly `ReconcileOrganizerStats` job overwrites these from source of truth to correct any missed increments.

### Models

- All models use `#[Guarded([])]` — all columns are mass-assignable
- Primary keys are ULIDs via the `HasUlids` trait
- Exception: `organizer_id` in stats tables is `char(26)` (ULID string), not an integer FK

### File Upload Pattern

In `store()` methods: use `$request->hasFile('field')` — never `isset()`.

In `update()` Form Requests, fields that accept either a new file upload or an existing URL string use an `imageOrUrl()` private closure (see `UpdateEventRequest`). In the controller:

```php
if ($request->hasFile('field')) {
    $data['field'] = asset('storage/' . $request->file('field')->store('path', 'public'));
} elseif (isset($data['field']) && is_string($data['field'])) {
    // keep existing URL as-is
} else {
    unset($data['field']); // omitted — leave DB column unchanged
}
```

When an update replaces a file, collect the old URL in a `$filesToDelete` array **before** the DB transaction, then delete the files **after** the transaction commits. See `EventService::update()` for the pattern.

### Caching

Cache tags follow the pattern `organizer:{$userId}:events:lists`. Flush this tag on any event write. All organizer dashboard reads (stats, chart, paginated list) are cached under this tag.

### updateOrCreate on hasOne Relationships

`$user->organizerProfile()->updateOrCreate([], $data)` works for upserts. For update-only operations (when the record is guaranteed to exist), use `$user->organizerProfile()->update($data)`. If a `contact_number` field is submitted to the profile endpoint, extract it and write it to `users.phone` before calling the profile update.

### Coding Conventions

- Validation belongs in Form Request classes (`app/Http/Requests/`), grouped by domain subfolder
- Responses use API Resource classes (`app/Http/Resources/`)
- Use `Rule::enum(EnumClass::class)` for enum validation
- Write migrations for every schema change; use `->after('column')` for placement
- Scheduled commands live in `routes/console.php`; register event listeners in `AppServiceProvider::boot()`
- `EventStatus` enum: `draft`, `published`, `cancelled`, `completed`
- For resources that may only be created once (e.g., banking info), check `$user->relation()->exists()` before creating and return HTTP 409 if already present.
- API Resource response keys don't need to match DB column names — map explicitly (e.g., `'venue_map' => $this->venue_map_image`).

### API Documentation

API docs live in a separate repo at `d:\Web\laragon\www\eventy_api_docs\`. Update the relevant `.md` file there whenever an endpoint is added or changed, and keep `readme.md` route summary in sync.

### For more

For more information and feedback use `.claude` folder in the project root.
