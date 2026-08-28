# Historical Booking & Session Backfill

Feature workspace: findings, decisions, and the phased implementation plan for entering
historical client data from paper files into the system.

**Project:** The Pilates Studio Syria — Operations Hub
**Branch:** `fix/booking-overbooking`
**Opened:** 2026-08-27
**Status:** **All decisions closed.** Ready for implementation — start at [Phase 1](plan/phase-1-schema-enum.md).

## Reading order

1. [`00-prd-summary.md`](00-prd-summary.md) — what the feature is
2. [`findings/`](findings/README.md) — A01–A14, verified against code
3. [`decisions/`](decisions/README.md) — codified rulings
4. [`plan/`](plan/README.md) — 9 phases, in execution order

## Rules for this folder

- One finding per file, one decision per file, one phase per file.
- Every claim carries a `file:line` reference verified on the branch above.
- A finding never states a ruling. A decision never restates evidence — it links to the finding.
- When a decision lands, update the finding's **Status** row and both index tables.

## Status at a glance

| ID | Finding | Impact | Status |
|---|---|---|---|
| A01 | Active-booking constraint is a DB generated column | High | **Decided** — [D-A01](decisions/D-A01-active-booking-conflict.md) |
| A02 | Packages with `validity_days = null` never expire | High | **Decided** — [D-A02](decisions/D-A02-null-validity-packages.md) |
| A03 | Credits decrement at reserve time, not attendance | — | Confirms PRD |
| — | Exchange rate for historical transactions | Medium | **Decided** — [D-A03](decisions/D-A03-exchange-rate-override.md) |
| A04 | No booking-expiry scheduler exists | High | Informs A01 |
| A05 | `purchased_at` column needed | Medium | **Decided** — [D-A05](decisions/D-A05-purchased-at-column.md) |
| A06 | `source_type` safe to extend | Low | Accepted |
| A07 | Cross-booking duplicate sessions unguarded | Medium | Accepted |
| A08 | Past-session capacity is informational | Low | Accepted |
| A09 | Frontend has no stepper; pagination is cursor | Medium | Accepted |
| A10 | Required indexes already present | — | No work |
| A11 | Idempotency is net-new | Medium | Accepted |
| A12 | Bookings have near-zero test coverage | Medium | Accepted |
| A13 | Backfill contaminates today's cash reconciliation | **Highest** | **Decided** — [D-A13](decisions/D-A13-global-purchased-at-adoption.md) |
| A14 | `class_sessions` never reach `completed` | Medium | Accepted |
| A15 | `booking_sessions` had no business date either | Medium | **Decided** — [D-A15](decisions/D-A15-booking-sessions-business-date.md) |

## Decisions

All six are closed. See [`decisions/`](decisions/README.md).

| ID | Ruling |
|---|---|
| [D-A01](decisions/D-A01-active-booking-conflict.md) | Active-booking conflict — hard rejection with a clear message |
| [D-A02](decisions/D-A02-null-validity-packages.md) | Null- and zero-validity packages — hard rejection |
| [D-A03](decisions/D-A03-exchange-rate-override.md) | Exchange rate — optional admin override, current-rate default |
| [D-A05](decisions/D-A05-purchased-at-column.md) | Add the `purchased_at` column |
| [D-A13](decisions/D-A13-global-purchased-at-adoption.md) | `purchased_at` becomes the project-wide business date |
| [D-A15](decisions/D-A15-booking-sessions-business-date.md) | `class_sessions.date` is the business date for `booking_sessions` |

> **D-A13 reaches beyond this feature.** It changes the date semantics of every financial query
> in the project and migrates existing data. Read it before touching Phase 1 or Phase 1.5.
