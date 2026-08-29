# A16 — Five write paths, four descriptions of one index

| | |
|---|---|
| **Verdict** | The production 1062 was one symptom of five |
| **Impact** | High — raw SQL errors reached admins |
| **Status** | Fixed 2026-08-29 |

## What the code did

`unique_active_booking_per_user` is a UNIQUE index over a stored generated column:

```sql
active_user_id = CASE WHEN status='active' AND remaining_credits>0 THEN user_id ELSE NULL END
```

Five code paths insert rows that can populate it. They were guarded by four different predicates,
three of which added an expiry clause the column does not have:

| Predicate | Where | Expiry clause | Matched the index |
|---|---|---|---|
| `findBlockingActiveBooking()` | `BookingEloquentRepository:195` | no | yes |
| `userHasActiveCreditBooking()` | `BookingEloquentRepository:73` | no | yes |
| `findActiveWithCreditsForUser()` | `BookingEloquentRepository:164` | **yes** | no |
| `Booking::blockingNewPurchase()` | `Booking.php` ACTIVE branch | **yes** | no |

A booking past `expires_at` that still says `active` with credits left is invisible to the
narrower three but still occupies the index. Those rows are routine, because nothing expires a
booking on a schedule ([A04](A04-no-expiry-scheduler.md)) — `expire()` is called by hand only.

## The five paths

| # | Path | Was |
|---|---|---|
| 1 | `BookingService::createFromPackage()` | correct predicate, but guard ran **outside** the transaction with no row lock — two concurrent requests both passed |
| 2 | walk-in `BookingSessionService::oneTimeAttend()` | expiry-filtered lookup returned null on a stale row, fell into the create-a-booking branch, inserted a second active row. No catch |
| 3 | `BookingFreezeService::unfreeze()` | **no active-booking check at all**; `UnfreezeBookingAction` caught `\Throwable` and returned `$e->getMessage()`, so the SQL string reached the admin |
| 4 | Filament create booking | `blockingNewPurchase()` expiry-filtered → unhandled Livewire exception |
| 5 | `BookingFactory` default state | `status=ACTIVE` with credits against a random existing user — two default-state calls can collide in tests |

## Root cause, and the fix

The predicates were the symptom. The cause is that a booking's status is never reconciled with its
own expiry date, so rows sit at `active` long after they stop meaning anything.

`BookingEloquentRepository::expireStaleActiveBookings(int $userId)` flips `status` to `EXPIRED`
where the row is `active` **and** `expires_at` has passed. Every write path calls it first. Once
stale rows stop existing, the index and every predicate agree by construction.

**This is reconciliation, not supersession.** It records a state change that already happened. A
booking with credits and a future expiry — or no expiry at all — is untouched and still blocks.
Superseding such a booking would destroy credits the client paid for, and was explicitly rejected
during planning.

On top of that: the guard moved inside the transaction and took a row lock (path 1), the walk-in
and unfreeze paths gained explicit checks (2, 3), the Filament scope lost its expiry clause (4),
and `Support\Booking\ActiveBookingConstraint` now recognises a 1062 on this index so a race that
slips through degrades to a localized message instead of a stack trace.

A scheduled sweep for all users — A04's actual fix — remains open.

## Tests

`tests/Feature/ActiveBookingConstraintTest.php`. Each drives one path and **fails explicitly on
`QueryException`**, which is what separates "guarded" from "happens to work today". Three
mutation-verified sentinels:

- restore the expiry clause in `blockingNewPurchase()` → the scope test fails
- remove the unfreeze guard → the unfreeze test fails
- make reconciliation ignore `expires_at` (i.e. supersede everything) → three tests fail,
  including the one proving a valid booking's credits survive
