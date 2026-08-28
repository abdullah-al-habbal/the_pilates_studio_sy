# PRD Summary

Source: Historical Booking & Session Backfill PRD, August 2026 (draft for review).

## Objectives (PRD §1)

1. Systematic historical data migration from paper into the system without corrupting digital records.
2. Reverse-sequential entry support — enter packages newest-first or oldest-first.
3. Temporal accuracy — the *actual* historical purchase date drives `expires_at`, not the data-entry date.
4. Precise session attribution — the admin explicitly names which sessions were attended and which were missed.
5. Credit reconciliation integrity — `total_credits = attended + missed + remaining` always holds.
6. Zero data loss; atomic, isolated, recoverable writes.
7. Native UX — reuse the existing Operations Hub modal / toast / shimmer / `__()` / `OperationsAPI` patterns.

## Flow (PRD §3.2)

```
package + currency + paid amount
  -> historical purchase date
  -> declare attended / missed counts
  -> pick exactly that many sessions from a paginated list inside the validity window
  -> review
  -> atomic submit
```

## Writes on submit (PRD §5)

Single transaction produces:

- one `bookings` row, `remaining_credits = total_credits − attended − missed`
- one `booking_sessions` row per attended session, `attendance_status = attended`
- one `booking_sessions` row per missed session, `attendance_status = missed`

## Where the PRD diverges from the codebase

| PRD claim | Reality | Finding |
|---|---|---|
| §12.3 "allow override of `assertNoActiveBooking` for historical sequencing" | Constraint is a DB generated column; cannot be overridden | [A01](findings/A01-active-booking-constraint.md) |
| §5.2 needs a schema decision on `expires_at` | `Booking::booted()` already honours a passed-in `expires_at` | [A05](findings/A05-purchased-at-column.md) |
| §11.2 stepper "extends existing patterns" | No stepper exists anywhere; net-new | [A09](findings/A09-frontend-reality.md) |
| §3.3 "cursor/offset pagination" | Ops modals use cursor + infinite scroll only | [A09](findings/A09-frontend-reality.md) |
| §15.4 "verify `class_sessions` indexes" | Already present | [A10](findings/A10-indexes-already-present.md) |
| §13 "reports can filter backfills if needed" | Nothing filters `source_type`; daily balance is contaminated | [A13](findings/A13-revenue-contamination.md) |
