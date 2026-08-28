# A04 — No booking-expiry scheduler exists

| | |
|---|---|
| **Verdict** | Gap; materially changes A01's risk profile |
| **Impact** | High |
| **Status** | Accepted — drives the guard choice in [D-A01](../decisions/D-A01-active-booking-conflict.md) |

## What the code does

`BookingEloquentRepository::expire()` (`:193`) sets `status = EXPIRED` — but it is only ever
called on demand. There is no automatic sweep:

- `routes/console.php` schedules three commands: reminders (x2) and logs.
- `app/Console/Commands/` holds six commands, none related to expiry.
- `app/Jobs/` holds only `SendOtpJob`.
- No model observer transitions booking status.

## Consequences

**1. The backfill must set its own terminal status.** Nothing will repair it later, so
`status` has to be computed and written at insert time.

**2. Stale `active` bookings are routine.** Any booking whose `expires_at` has passed keeps
`status = active` indefinitely. If it also has `remaining_credits > 0`, it **still occupies
`active_user_id`** — because the generated column in [A01](A01-active-booking-constraint.md)
tests only `status` and `remaining_credits`, never `expires_at`.

This is why the A01 collision is common rather than rare, and why any conflict guard must use
an **expiry-unfiltered** predicate. See [D-A01 §Corrections](../decisions/D-A01-active-booking-conflict.md).
