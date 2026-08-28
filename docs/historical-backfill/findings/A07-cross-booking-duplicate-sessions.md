# A07 — Cross-booking duplicate sessions are not guarded by the database

| | |
|---|---|
| **Verdict** | Gap the PRD does not name |
| **Impact** | Medium |
| **Status** | Accepted — reuse the existing app-level guard |

## What the code does

The database unique constraint is scoped to a single booking:

```php
// database/migrations/2024_01_01_000056_create_booking_sessions_table.php:75
$table->unique(['booking_id', 'class_session_id'], 'unique_booking_session');
```

The cross-booking check lives in application code only:

```php
// app/Services/BookingSession/BookingSessionService.php:267
assertNoDuplicateSessionForUser(int $userId, int $classSessionId)
```

which queries for an existing RESERVED `booking_sessions` row for that user + class session.

## Consequence

`unique_booking_session` stops the same booking claiming a session twice. It does **not** stop
the same client being backfilled into the same past session via two different bookings —
exactly what sequential backfill of several historical packages invites.

The backfill service must call `assertNoDuplicateSessionForUser()` per selected session.
