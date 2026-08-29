# Phase 2 — Domain core

Build and test headless, before any HTTP or UI work.

**Blocking on:** Phase 1, Phase 1.5.
**Implements:** [D-A01](../decisions/D-A01-active-booking-conflict.md), [D-A02](../decisions/D-A02-null-validity-packages.md), [D-A03](../decisions/D-A03-exchange-rate-override.md), [D-A05](../decisions/D-A05-purchased-at-column.md).

## Steps

### 2.0 Standard bookings adopt the business date

`app/Services/Booking/BookingService.php` — `createFromPackage()` adds `'purchased_at' => now()`
to its create array ([D-A13](../decisions/D-A13-global-purchased-at-adoption.md)).

The Phase 1 model hook already guarantees this, but the call site that represents a real sale
should say so explicitly.

### 2.1 Repository — the correct conflict predicate

`app/Repositories/Eloquent/Booking/BookingEloquentRepository.php`

Add:

```php
findBlockingActiveBooking(int $userId): ?Booking
```

- predicate exactly `status = ACTIVE AND remaining_credits > 0`
- **no expiry filter** — must match the generated column, per [D-A01 §C1](../decisions/D-A01-active-booking-conflict.md)
- eager-load `package`, so one query serves both the boolean guard and the message placeholders

> **Updated 2026-08-29.** This section used to warn against reusing `existsActiveWithCredits()`
> and `assertNoActiveBooking()` because both filtered expiry and so missed stale rows
> ([A04](../findings/A04-no-expiry-scheduler.md)). `existsActiveWithCredits()` has since been
> deleted and `assertNoActiveBooking()` now uses this same predicate, under a row lock, after
> reconciling stale rows. See [A16](../findings/A16-booking-insert-paths.md), which converged all
> five write paths onto it.

### 2.2 Validator

`app/Services/Validation/HistoricalBackfillValidatorService.php`

Rules run in this order. The order is load-bearing — see the note below.

**1. Package validity gate — [D-A02](../decisions/D-A02-null-validity-packages.md).** Runs
*first*, before price validation and before the D-A01 terminal-status check:

```php
if (! ($package->validity_days > 0)) {
    throw ValidationException::withMessages([
        'package_id' => __('dashboard.operations_ui.historical_backfill.error_null_validity_package'),
    ]);
}
```

Test `> 0`, **not** `=== null`. `validity_days = 0` is reachable (`CreatePackageRequest.php:21`
allows `min:0`), is rendered to admins as "Unlimited" (`PackageInfolist.php:54`), and produces
the same permanent-`ACTIVE` booking as `null`.

**2. Price** — reuse `AssignPackageValidatorService::validateAndComputeAmount()`.

**3. Everything else:**

- purchase date required, valid, not in the future
- `attended + missed <= total_credits`
- `count(attended_session_ids) === attended_count`; same for missed
- `attended ∩ missed = ∅`
- all session IDs exist and fall inside `[purchased_at, expires_at]`
- optional exchange rate — `['nullable','numeric','min:0.000001','max:999999']`
  ([D-A03](../decisions/D-A03-exchange-rate-override.md); the non-zero minimum is load-bearing,
  see that file)
- **terminal-status computation** — [D-A01 reference impl, step 1](../decisions/D-A01-active-booking-conflict.md)
- **A01 conflict guard** — [D-A01 reference impl, step 2](../decisions/D-A01-active-booking-conflict.md), running before any write and *instead of* `assertNoActiveBooking()`

> **Why the order matters.** The D-A02 gate must precede the D-A01 terminal-status computation,
> because that computation calls `addDays($package->validity_days)`. With the gate first, the
> `null`/`0` case is already rejected and `addDays()` can never receive a non-positive value.

### 2.3 Service

`app/Services/Booking/HistoricalBackfillService.php`

Single `DB::transaction`. **Lock order: class_sessions → booking**, matching `reserve()` and
`oneTimeAttend()` — see the deadlock comment at `BookingSessionService.php:196-201`.

1. resolve package
2. compute `expiresAt` and `terminalStatus` per D-A01
3. create booking: `source_type = HISTORICAL_BACKFILL`, `purchased_at` = the admin-entered
   historical date ([D-A05](../decisions/D-A05-purchased-at-column.md)), explicit `expires_at`,
   explicit `exchange_rate_snapshot` from the resolved rate
   ([D-A03](../decisions/D-A03-exchange-rate-override.md) — passing it non-zero suppresses the
   `static::saving` auto-compute at `Booking.php:77-85`), computed `remaining_credits` and
   `status`, `created_by = auth()->id()`
4. insert `booking_sessions`
   - attended: `attendance_status = ATTENDED`, `attended_at` = the session's datetime, `attendance_updated_by = auth()->id()`
   - missed: `attendance_status = MISSED`, `attended_at = null`
5. per selected session, call `assertNoDuplicateSessionForUser()` ([A07](../findings/A07-cross-booking-duplicate-sessions.md))

**Skip the capacity assert** ([A08](../findings/A08-past-session-capacity.md)) — the event
already happened.

### 2.4 Safety net

Catch `QueryException`, narrowed to driver code `1062` **and** `unique_active_booking_per_user`
in the message; rethrow as the same localized `ValidationException`. Every other
`QueryException` propagates untouched. Per [D-A01 §C3](../decisions/D-A01-active-booking-conflict.md).

### 2.5 Audit logging

`LoggingService`, per PRD §8.3: admin ID, client ID, package ID, purchase date, counts,
selected session IDs, IP, timestamp. Log validation failures and rollbacks too — PRD §8.4
requires no silent failures.

## Done when

- The D-A02 gate rejects `null` **and** `0` validity before any other rule runs.
- An explicitly passed `exchange_rate_snapshot` survives the save untouched.
- `purchased_at` holds the historical date while `created_at` holds the entry timestamp.
- The service can be driven from a test with no HTTP layer.
- Every [Phase 7](phase-7-tests.md) backend case passes.
