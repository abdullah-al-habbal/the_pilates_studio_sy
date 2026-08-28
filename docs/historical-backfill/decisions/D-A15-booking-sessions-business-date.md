# D-A15 — `class_sessions.date` is the business date for `booking_sessions`

| | |
|---|---|
| **Motivating finding** | [A15](../findings/A15-booking-session-business-date.md) |
| **Mirrors** | [D-A13](D-A13-global-purchased-at-adoption.md) |
| **Date** | 2026-08-28 |
| **Status** | Accepted |

## Ruling

**The business date for a `booking_sessions` row is the date of the class session it points at —
`class_sessions.date` — never `booking_sessions.created_at`.**

Every attendance report, count, trend, and period filter over `booking_sessions` must resolve the
date through `class_session_id`. `created_at` remains audit and ordering only.

## Rationale

Same principle as [D-A13](D-A13-global-purchased-at-adoption.md), applied to the sessions table:
the date a row was *written* is not the date the thing *happened*. Historical backfill makes the
gap months wide, so a report keyed on `created_at` attributes an entire paper history to the day
an admin typed it in.

## No new column

Unlike `bookings`, which needed `purchased_at`, `booking_sessions` already has its business date
one join away. Adding a denormalised copy would duplicate `class_sessions.date` and create a
second source of truth to keep in sync. Join instead.

## Implementation rule

```php
->join('class_sessions', 'booking_sessions.class_session_id', '=', 'class_sessions.id')
->whereNull('class_sessions.deleted_at')
```

**The `deleted_at` guard is mandatory.** `ClassSession` uses `SoftDeletes`, and a raw join
bypasses the global scope — without it, soft-deleted sessions re-enter every report. This is a
requirement of the join, not an optional filter.

Prefer the join over `whereHas`: the aggregate has to select and group by `class_sessions.date`
anyway, so `whereHas` would add a redundant correlated subquery on top of a join that must exist
regardless.

Qualify column names on both sides once joined — `booking_sessions.attendance_status`, not
`attendance_status` — or MySQL raises an ambiguous-column error on the shared column names.

## Scope

Applied to the two offenders found in the sweep:

| Method | Was | Now |
|---|---|---|
| `BookingSessionEloquentRepository::getAttendanceTrend()` | `DATE(created_at)`, grouped and filtered | `class_sessions.date` |
| `BookingSessionEloquentRepository::countMissedForMonth()` | `whereMonth/whereYear('created_at')` | `whereMonth/whereYear('class_sessions.date')` |

The sweep found no others — see [A15](../findings/A15-booking-session-business-date.md).

## Applies going forward

[Phase 2](../plan/phase-2-domain-core.md) writes historical `booking_sessions`, and later phases
may add attendance reporting. Any new period-based query over `booking_sessions` follows this
rule. `attended_at` is likewise set from the session's own datetime, not from `now()`.

## Plan impact

| Phase | Change |
|---|---|
| [Phase 2](../plan/phase-2-domain-core.md) | `attended_at` derives from the class session datetime — already specified in step 4 |
| [Phase 7](../plan/phase-7-tests.md) | Covered by `tests/Feature/AttendanceBusinessDateTest.php` (5 tests, 2 mutation-verified) |
