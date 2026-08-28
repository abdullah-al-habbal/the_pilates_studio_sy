# Phase 1.5 — Financial date migration

**BLOCKING.** No backfilled booking may be written before this lands.
**Implements:** [D-A13](../decisions/D-A13-global-purchased-at-adoption.md).
**Depends on:** Phase 1 (column + `UPDATE` + model hook must already exist).

> **Scope changed.** This phase was originally "exclude backfills from revenue queries". Under
> [D-A13 §C3](../decisions/D-A13-global-purchased-at-adoption.md) that exclusion is dropped —
> keying on `purchased_at` achieves the same result more precisely, and the exclusion would have
> wrongly hidden same-day paper sales. The phase is now the date migration itself.

## Why this is a gate, not a follow-up

`DailyBalanceService.php:70` reads `getRevenueByCurrency($start, $end)`, which filters on
`created_at`. Until that becomes `purchased_at`, entering historical paper packages books the
money into **today's** revenue and true balance in the Operations finance tab.

There is no error and no visible symptom — the first bulk backfill session silently falsifies
the dashboard until someone counts the cash drawer.

## Not in scope: merchandise, expenses, refunds

They already key on their own business dates — `ordered_at`, `expense_date`, `refunded_at` — at
every query site. `bookings` was the only financial entity without one. Table of evidence in
[D-A13](../decisions/D-A13-global-purchased-at-adoption.md).

## Steps

### 1.5.1 Migrate the date filters

`app/Repositories/Eloquent/Booking/BookingEloquentRepository.php` — change `created_at` to
`purchased_at` in all four:

| Method | Lines |
|---|---|
| `getRevenueByCurrency()` | `:26-27` |
| `getRevenueWithExchangeSnapshot()` | `:52-53` |
| `getTotalCount()` | `:68-69` |
| `getTotalRevenueByCurrency()` | `:222-223` |

`getRevenueByPackage()` (`:100-112`) needs **no change** — verified: it groups only, with no
date filter.

`DailyBalanceService` (`:70`) has no date filter of its own; it inherits the repository change.

### 1.5.2 `ExchangeRateSnapshotService` — migrate **and** exclude

`app/Services/Finance/ExchangeRateSnapshotService.php:28-32` — the Booking branch migrates to
`purchased_at` like the rest, bringing it in line with the two branches below it, which already
use business dates (`ordered_at` at `:38-41`, `refunded_at` at `:48-51`).

It **also** needs an exclusion, and that is what does the real work:

```php
->where('source_type', '!=', BookingSourceTypeEnum::HISTORICAL_BACKFILL->value)
```

A backfill carries today's rate by default while its `purchased_at` sits months back; excluded,
every remaining booking has `purchased_at == created_at` by construction, so the two columns are
behaviourally identical here. Reasoning in
[D-A13 §C2](../decisions/D-A13-global-purchased-at-adoption.md).

**Both changes are required.** The exclusion alone leaves an inconsistency; the migration alone
corrupts historical rates.

### 1.5.3 Do **not** add a `source_type` exclusion to the revenue queries

Explicitly out of scope, per [D-A13 §C3](../decisions/D-A13-global-purchased-at-adoption.md).
`purchased_at` already places each booking in its correct period; an exclusion on top would hide
legitimate same-day paper sales.

## Done when

- All four repository methods filter `purchased_at`.
- `getRevenueByPackage()` is untouched.
- `ExchangeRateSnapshotService` filters `purchased_at` **and** excludes backfills.
- A booking backfilled today with `purchased_at` two months back is absent from today's balance.
- A booking backfilled today with `purchased_at = today` **is present** in today's balance.
