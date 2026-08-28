# A01 — Active-booking constraint is a database generated column

| | |
|---|---|
| **Verdict** | Contradicts PRD §12.3 |
| **Impact** | High |
| **Status** | Decided — [D-A01](../decisions/D-A01-active-booking-conflict.md) |

## What the code does

`bookings` carries a stored generated column and a unique index over it:

```sql
-- database/migrations/2024_01_01_000005_create_bookings_table.php:~84-92
active_user_id = CASE WHEN status='active' AND remaining_credits>0 THEN user_id ELSE NULL END
UNIQUE(active_user_id)   -- unique_active_booking_per_user
```

This is enforced by MySQL, not by application code. It cannot be bypassed by a service-layer
flag, a scope, or an `if`. The only way through is to make the expression evaluate to `NULL`.

## Consequence for the PRD

PRD **Scenario B** — `remaining_credits = 2`, `status = active` — raises a raw
`QueryException` (driver code 1062) whenever the client already has an active package.
Nothing in the app catches 1062 today.

PRD §12.3 proposes "reuse `assertNoActiveBooking()` … but allowing override for historical
sequencing". No such override is possible.

## Which outcomes are safe

| Terminal state | `active_user_id` | Insert succeeds |
|---|---|---|
| `remaining_credits = 0` | `NULL` | Yes |
| `status != active` (`expired`, `exhausted`, `cancelled`, `frozen`) | `NULL` | Yes |
| `status = active` **and** `remaining_credits > 0` | `user_id` | Only if no other active booking |

## Related

- [A04](A04-no-expiry-scheduler.md) — stale `active` bookings make this collision common, not rare
- [A02](A02-null-validity-packages.md) — null-validity packages can never reach a safe state
