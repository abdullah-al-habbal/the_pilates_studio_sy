# Implementation plan — 9 phases

Execute in order. Phase 1.5 is blocking; nothing may write a backfilled booking before it lands.

| # | Phase | Blocking on | Gate |
|---|---|---|---|
| 1 | [Schema + enum](phase-1-schema-enum.md) | — (unblocked by [D-A05](../decisions/D-A05-purchased-at-column.md)) | — |
| 1.5 | [Financial date migration](phase-1.5-revenue-isolation.md) | — (unblocked by [D-A13](../decisions/D-A13-global-purchased-at-adoption.md)) | **Blocks all later phases** |
| 2 | [Domain core](phase-2-domain-core.md) | Phase 1, 1.5 | Testable headless |
| 3 | [Session-picker endpoint](phase-3-session-picker-endpoint.md) | Phase 1 | — |
| 4 | [Submit endpoint](phase-4-submit-endpoint.md) | Phase 2 | — |
| 5 | [Frontend](phase-5-frontend.md) | Phase 3, 4 | — |
| 6 | [i18n](phase-6-i18n.md) | Phase 5 key list | Parity check must pass |
| 7 | [Tests](phase-7-tests.md) | Phase 2 onward | Green suite |
| 8 | [Docs + formatting](phase-8-docs.md) | all | `pint` clean |

## Progress

| # | Phase | Status |
|---|---|---|
| 1 | Schema + enum | **Done** — consolidated into `2024_01_01_000005_create_bookings_table`, enum case, model fillable/cast/hook |
| 1.5 | Financial date migration | **Done** — 4 repo methods + `ExchangeRateSnapshotService` |
| 2–8 | — | Not started |

Covered by `tests/Feature/BookingPurchasedAtTest.php` (11 tests). Full suite: 112 passing.

Three sentinels were mutation-verified — each fails when its guard is removed:
reverting the repo to `created_at`, dropping the backfill exclusion from
`ExchangeRateSnapshotService`, and reintroducing a `source_type` exclusion into the revenue
queries.

## Sequencing notes

- **Backend before frontend.** Phases 2–4 are fully testable without any UI.
- **Phase 3 before Phase 5** — the picker module cannot be built against a missing endpoint.
- **Phase 7 runs continuously**, not at the end. Each backend phase lands with its tests.

## Decisions already folded in

| Decision | Lands in |
|---|---|
| [D-A01](../decisions/D-A01-active-booking-conflict.md) — active-booking conflict | Phase 2, 6, 7 |
| [D-A02](../decisions/D-A02-null-validity-packages.md) — null-validity packages | Phase 2, 6, 7 |
| [D-A03](../decisions/D-A03-exchange-rate-override.md) — exchange-rate override | Phase 2, 4, 5, 6, 7 |
| [D-A05](../decisions/D-A05-purchased-at-column.md) — `purchased_at` column | Phase 1, 2, 7 |
| [D-A13](../decisions/D-A13-global-purchased-at-adoption.md) — global `purchased_at` business date | Phase 1, 1.5, 2, 7 |
| [D-A15](../decisions/D-A15-booking-sessions-business-date.md) — `booking_sessions` business date | Phase 1.5, 2, 7 |

All decisions are closed. The plan is ready for implementation.

> **D-A13 is project-wide.** It migrates existing booking data and changes the date semantics of
> every financial query — not only the backfill path. Read it before starting Phase 1.
