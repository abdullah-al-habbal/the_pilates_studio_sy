# D-A05 — Add a `purchased_at` column to `bookings`

| | |
|---|---|
| **Motivating finding** | [A05](../findings/A05-purchased-at-column.md) |
| **Date** | 2026-08-27 |
| **Status** | Accepted |

## Ruling

**Add a nullable `timestamp purchased_at` to `bookings`, positioned after `expires_at`, with an
index on `(user_id, purchased_at)`.**

## Rationale

- `created_at` must stay the **true system timestamp** of data entry — audit, logs, causality.
- Without `purchased_at`, the historical purchase date is unrecoverable. Back-deriving
  `expires_at − validity_days_snapshot` is semantically wrong and fragile (it assumes the
  package's validity never changed after purchase).
- Nullable, so existing rows are untouched. Standard bookings leave it `null` and fall back to
  `created_at`; backfills populate it.

## Schema

```php
$table->timestamp('purchased_at')->nullable()->after('expires_at');
$table->index(['user_id', 'purchased_at']);
```

## Model

```php
// app/Models/Booking.php
protected $fillable = [ /* … */ 'purchased_at' ];

protected function casts(): array
{
    return [ /* … */ 'purchased_at' => 'datetime' ];
}
```

## Interaction with the `creating` hook — no conflict

`Booking::booted()`'s `static::creating` computes `expires_at` from
`$booking->created_at ?? now()` (`app/Models/Booking.php:63-75`) — it does **not** read
`purchased_at`, and it skips entirely when `expires_at` is already set.

The backfill passes an explicit `expires_at` (computed from `purchased_at + validity_days`), so
the hook is a no-op on that path. Adding the column changes nothing about existing behaviour.

## Correction applied to the drafted plan items

### C1 — Phase 3 needs no change

The drafted item read: *"Session-picker endpoint: use `purchased_at` (if set) or `created_at`
for date-range queries when relevant."*

The picker runs **before the booking exists**. Its date window comes straight from the
admin-entered date in the request query string; there is no booking row to read `purchased_at`
from. [Phase 3](../plan/phase-3-session-picker-endpoint.md) is unaffected by this decision.

The column does become relevant later, to reporting — see the `COALESCE(purchased_at, created_at)`
option in [A13](../findings/A13-revenue-contamination.md), which depends on this decision.

## Plan impact

| Phase | Change |
|---|---|
| [Phase 1](../plan/phase-1-schema-enum.md) | Migration + model changes (already drafted — now unblocked) |
| [Phase 2](../plan/phase-2-domain-core.md) | Service sets `purchased_at` to the admin-entered historical date |
| [Phase 3](../plan/phase-3-session-picker-endpoint.md) | **No change** — see C1 |
| [Phase 7](../plan/phase-7-tests.md) | Assert `purchased_at` stores the historical date **and** `created_at` remains the entry timestamp |
