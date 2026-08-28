# A08 — Past-session capacity should be informational only

| | |
|---|---|
| **Verdict** | Agrees with PRD §10 |
| **Impact** | Low |
| **Status** | Accepted |

## What the code does

Available spots are computed from RESERVED rows and clamped at zero:

```php
// app/Repositories/Eloquent/ClassSession/ClassSessionEloquentRepository.php:170
$reserved = $session->bookingSessions()->where('status', RESERVED)->count();
return max(0, $capacity - $reserved);
```

The same logic backs the `available_spots` accessor on `app/Models/ClassSession.php:156`.

There is also **no app-level guard against reserving a past session** — neither
`ClassSessionService::hasAvailableSpots()` nor `ReserveSessionRequest` filters on date, and
the database permits the insert.

## Consequence

Backfill writes RESERVED rows against past sessions, retroactively consuming their spots.
A past session that was genuinely over-subscribed (walk-ins, manual overrides) will read as
full in the scheduler.

No crash — `max(0, …)` clamps the value. The event already happened, so blocking on capacity
would be wrong. **Skip the capacity assert in the backfill path** and accept the display effect.
