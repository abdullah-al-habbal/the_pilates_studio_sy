# Phase 7 — Tests

`tests/Feature/HistoricalBackfillTest.php`

Run alongside each backend phase, not at the end.
**Infrastructure:** see [A12](../findings/A12-test-coverage.md) — MySQL `pilates_studio_test_db`,
`RefreshDatabase`, `PHPUnit\Framework\Attributes\Test`, `final class`, factories for every model.

Running on MySQL matters: `unique_active_booking_per_user` is a generated column, so the A01
behaviour is genuinely exercised.

## Cases

### Happy paths

| # | Case | Expect |
|---|---|---|
| 1 | Scenario A — complete backfill | `remaining = 0`, status `EXHAUSTED` |
| 2 | Leftover credits, package still valid, **no** active booking | status `ACTIVE`, succeeds |
| 3 | Expired on create (`purchased_at + validity_days < now()`) | status `EXPIRED`, leftover credits forfeited |
| 4 | Zero attended + zero missed | shortcut path, no session selection required |

### D-A01 rejection

| # | Case | Expect |
|---|---|---|
| 5 | `it_rejects_backfill_when_user_has_active_booking_and_old_package_is_still_valid` | `ValidationException` with the localized key — **not** `QueryException`; asserts **zero** rows written |
| 6 | **Stale-active-booking variant** — conflicting booking has `expires_at` in the past but `status = active` with credits left | Rejected by the **validator**, not by the database. Covers [D-A01 §C1](../decisions/D-A01-active-booking-conflict.md) / [A04](../findings/A04-no-expiry-scheduler.md) |

### D-A02 rejection

| # | Case | Expect |
|---|---|---|
| 7 | `it_rejects_backfill_for_null_validity_package` — `validity_days = null` | `ValidationException` with `error_null_validity_package`; zero rows written |
| 8 | **Zero-validity variant** — `validity_days = 0` | Same rejection. Fails if the guard is written as `=== null` instead of `> 0`. See [D-A02](../decisions/D-A02-null-validity-packages.md) |
| 9 | Null-validity package where `attended + missed = total_credits` | Still rejected — the gate is categorical and runs before counts |

### Validation

| # | Case |
|---|---|
| 10 | Declared count vs selected count mismatch (attended, then missed) |
| 11 | Attended ∩ missed non-empty |
| 12 | Selected session outside the validity window |
| 13 | Cross-booking duplicate session ([A07](../findings/A07-cross-booking-duplicate-sessions.md)) |
| 14 | Future purchase date |

### Exchange rate — [D-A03](../decisions/D-A03-exchange-rate-override.md)

| # | Case | Expect |
|---|---|---|
| 15 | `it_uses_admin_provided_exchange_rate_when_present` | Stored snapshot equals the submitted rate, not today's |
| 16 | `it_uses_current_rate_when_exchange_rate_snapshot_is_null` | Falls back to `getExchangeRateForSnapshot()` |
| 17 | Rate below `0.000001` is rejected | Guards the falsy-zero hazard: a stored `0` would let `static::saving` (`Booking.php:77-85`) overwrite the historical rate with today's on the next save |

### Timestamps — [D-A05](../decisions/D-A05-purchased-at-column.md)

| # | Case | Expect |
|---|---|---|
| 18 | `purchased_at` stores the historical date **and** `created_at` stays the entry timestamp | Both distinct, both correct |
| 19 | `expires_at` derives from `purchased_at + validity_days`, not from `created_at` | Proves the `creating` hook was bypassed by the explicit value |

### Business date — [D-A13](../decisions/D-A13-global-purchased-at-adoption.md)

| # | Case | Expect |
|---|---|---|
| 20 | `it_reports_revenue_by_purchased_at_not_created_at` | A booking entered today with `purchased_at` two months back appears in that month's revenue, not today's |
| 21 | **Same-day paper sale** — backfilled today with `purchased_at = today` | **Present** in today's balance. Fails if a `source_type` exclusion is reintroduced ([D-A13 §C3](../decisions/D-A13-global-purchased-at-adoption.md)) |
| 22 | Standard booking sets `purchased_at = now()` | Equal to `created_at` within tolerance |
| 23 | Migration backfills existing rows | Every pre-existing booking ends with `purchased_at = created_at` |
| 24 | `getHistoricalRate()` ignores backfilled bookings | A backfill carrying today's rate does not become the historical rate for its `purchased_at` period. The service filters `purchased_at` **and** excludes backfills — both are required ([D-A13 §C2](../decisions/D-A13-global-purchased-at-adoption.md)) |

### Integrity

| # | Case |
|---|---|
| 25 | Double-submit idempotency ([A11](../findings/A11-idempotency-net-new.md)) |
| 26 | Credit arithmetic invariant: `total = attended + missed + remaining` |

## Regression sentinels

Two cases exist specifically to catch a correct-looking but wrong guard. Neither may be
weakened:

- **Case 6** fails if the D-A01 guard is swapped back to an expiry-filtered helper.
- **Case 8** fails if the D-A02 guard is written as `=== null` instead of `> 0`.
- **Case 17** fails if the rate minimum is relaxed to `min:0`.
- **Case 19** fails if the service stops passing an explicit `expires_at` and lets the
  `creating` hook compute it from `created_at`.
- **Case 21** fails if a `source_type` exclusion is reintroduced into the revenue queries.
- **Case 24** fails if the backfill exclusion is dropped from `ExchangeRateSnapshotService` —
  migrating its date column alone is not sufficient.

## Done when

All twenty-six pass on MySQL.
