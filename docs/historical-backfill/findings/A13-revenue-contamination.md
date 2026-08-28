# A13 — Backfill contaminates today's cash reconciliation

| | |
|---|---|
| **Verdict** | Highest-impact finding; PRD §13 assumes a filter that does not exist |
| **Impact** | **Highest** |
| **Status** | Decided — [D-A13](../decisions/D-A13-global-purchased-at-adoption.md) |

## What the code does

The Operations finance tab and sidebar quick-stats read through `DailyBalanceService`:

```php
// app/Services/Finance/DailyBalanceService.php:70
$pkgRevenueRaw = $this->bookingRepo->getRevenueByCurrency($start, $end, $creatorId)
```

and that query filters on **`created_at`**, with no `source_type` predicate:

```php
// app/Repositories/Eloquent/Booking/BookingEloquentRepository.php:17-34
->whereNotNull('paid_amount')
->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
->when($endDate,   fn ($q) => $q->where('created_at', '<=', $endDate))
```

Same shape in `getRevenueWithExchangeSnapshot()` `:37-62` and `getRevenueByPackage()` `:100-112`.

## Consequence

Entering 200 historical paper packages today books **all** of that money into today's package
revenue and true balance. The studio's daily cash-drawer count will not match the dashboard.

PRD §13 states "historical backfills are tagged with a distinct `source_type`; reports can
filter them if needed". Nothing filters them ([A06](A06-source-type-extension.md)), so the
tag alone changes nothing.

## Two separate concerns

| Concern | Correct behaviour | Fix |
|---|---|---|
| Daily balance — money that moved today | Backfills must be **excluded** | `->where('source_type', '!=', 'historical_backfill')` on the 3 queries |
| Period revenue history — business truth | Backfills belong in their **historical** period | date-filter on `COALESCE(purchased_at, created_at)` |

**Resolved by [D-A13](../decisions/D-A13-global-purchased-at-adoption.md)**, which took a
third route: migrate existing rows so `purchased_at` is always populated, then query it
directly — no `COALESCE` anywhere. That also made the `source_type` exclusion unnecessary, and
in fact harmful for same-day paper sales. See [D-A13 §C3](../decisions/D-A13-global-purchased-at-adoption.md).

Note also: the query list above is incomplete. `getTotalRevenueByCurrency()` (`:216-225`) filters
`created_at` as well, and `ExchangeRateSnapshotService::getHistoricalRate()` (`:28-32`) filters it
for a different purpose that must **not** migrate. Both handled in D-A13.

## Why this blocks

Without the exclusion, the very first bulk backfill session silently falsifies the finance tab
with no error and no visible symptom until someone counts the drawer.
