# Findings A01–A15

Each finding was verified against code on branch `fix/booking-overbooking` (2026-08-27).
A finding records **what the code does**. It never records a ruling — see [`../decisions/`](../decisions/README.md).

| ID | Title | Impact | Status |
|---|---|---|---|
| [A01](A01-active-booking-constraint.md) | Active-booking constraint is a DB generated column | High | Decided — [D-A01](../decisions/D-A01-active-booking-conflict.md) |
| [A02](A02-null-validity-packages.md) | Packages with `validity_days = null` never expire | High | Decided — [D-A02](../decisions/D-A02-null-validity-packages.md) |
| [A03](A03-credit-decrement-timing.md) | Credits decrement at reserve time, not attendance | — | Confirms PRD |
| [A04](A04-no-expiry-scheduler.md) | No booking-expiry scheduler exists | High | Informs A01 |
| [A05](A05-purchased-at-column.md) | `purchased_at` column needed for historical date | Medium | Decided — [D-A05](../decisions/D-A05-purchased-at-column.md) |
| [A06](A06-source-type-extension.md) | `source_type` is safe to extend | Low | Accepted |
| [A07](A07-cross-booking-duplicate-sessions.md) | Cross-booking duplicate sessions unguarded | Medium | Accepted |
| [A08](A08-past-session-capacity.md) | Past-session capacity is informational only | Low | Accepted |
| [A09](A09-frontend-reality.md) | No stepper exists; pagination is cursor-based | Medium | Accepted |
| [A10](A10-indexes-already-present.md) | Required `class_sessions` indexes already present | — | No work |
| [A11](A11-idempotency-net-new.md) | Idempotency mechanism is net-new | Medium | Accepted |
| [A12](A12-test-coverage.md) | Bookings have near-zero test coverage | Medium | Accepted |
| [A13](A13-revenue-contamination.md) | Backfill contaminates today's cash reconciliation | **Highest** | Decided — [D-A13](../decisions/D-A13-global-purchased-at-adoption.md) |
| [A14](A14-sessions-never-completed.md) | `class_sessions` never transition to `completed` | Medium | Accepted |
| [A15](A15-booking-session-business-date.md) | `booking_sessions` had no business date either | Medium | Decided — [D-A15](../decisions/D-A15-booking-sessions-business-date.md) |
