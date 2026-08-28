# Phase 3 — Session-picker endpoint

**Blocking on:** Phase 1. **Blocks:** [Phase 5](phase-5-frontend.md).

## Steps

### 3.1 Route

`routes/web/operations/bookings.php`

```php
Route::get('/backfill/sessions', GetBackfillSessionsAction::class)->name('backfill.sessions');
```

Full URI: `GET /admin/operations/bookings/backfill/sessions`, name
`admin.operations.bookings.backfill.sessions`.

Middleware is inherited from `routes/web/operations/index.php:9` —
`['web', 'auth', 'freeze.user', 'role.admin']`. Both aliases verified at
`bootstrap/app.php:63-64`. No new middleware needed; PRD §7 is satisfied by the group.

### 3.2 Action, handler, resource

- `app/Http/Actions/Web/Admin/Operations/GetBackfillSessionsAction.php`
- matching handler under `app/Handlers/Admin/Operations/`
- `BackfillSessionResource` — id, date, start/end time, localized class title, instructor name, reserved count

### 3.3 Query

```php
->whereDate('date', '<=', today())
->where('status', '!=', ClassSessionStatusEnum::CANCELLED->value)
->whereBetween('date', [$purchasedAt, $expiresAt])
->orderBy('date')->orderBy('start_time')
```

**Do not filter `status = completed`** — nothing in the system ever sets it, so that returns
zero rows and a silently empty picker. See [A14](../findings/A14-sessions-never-completed.md).

Covered by the existing `idx_date_status` index ([A10](../findings/A10-indexes-already-present.md)).

### 3.4 Pagination

**Cursor**, returning `{data, meta: {next_cursor, has_more}}` — mirroring
`OperationsAPI.getClientsCursor`. The ops UI has no offset-pagination pattern in modals
([A09](../findings/A09-frontend-reality.md)).

## Done when

- The endpoint returns past, non-cancelled sessions inside the validity window.
- Cursor paging round-trips and `has_more` is correct at the boundary.
