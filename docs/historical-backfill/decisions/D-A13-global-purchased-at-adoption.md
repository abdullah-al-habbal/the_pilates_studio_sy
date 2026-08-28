# D-A13 — Global `purchased_at` adoption: business date vs audit date

| | |
|---|---|
| **Motivating finding** | [A13](../findings/A13-revenue-contamination.md) |
| **Depends on** | [D-A05](D-A05-purchased-at-column.md) |
| **Date** | 2026-08-27 |
| **Status** | Accepted |
| **Scope** | **Project-wide** — not limited to the backfill feature |

## Ruling

**`purchased_at` becomes the canonical business date for all bookings. `created_at` is
relegated to audit and technical use.** Every financial query, revenue calculation, and
period filter reads `purchased_at`.

Strategy: **full migration** — backfill the column for existing rows, then query it directly.
No `COALESCE` in application code.

## Rationale

- `created_at` answers *"when was this row written"* — audit, logs, causality.
- `purchased_at` answers *"when did the customer actually buy"* — the only date a financial
  report should care about.
- Full migration keeps queries simple and indexable. `COALESCE(purchased_at, created_at)` in
  every query is slower, unindexable on the composite, and spreads the ambiguity across the
  codebase forever.
- After the one-time `UPDATE`, `purchased_at` is guaranteed present on every row.

## Implementation

### Step 1 — Data migration

In the same migration that adds the column:

```php
$table->timestamp('purchased_at')->nullable()->after('expires_at');
$table->index(['user_id', 'purchased_at']);
// …then, after the column exists:
DB::statement('UPDATE bookings SET purchased_at = created_at WHERE purchased_at IS NULL');
```

The raw statement covers soft-deleted rows too, which is correct — historical revenue does not
disappear because a booking was later soft-deleted.

### Step 2 — Model hook

```php
// app/Models/Booking.php — inside the existing static::creating
if (! $booking->purchased_at) {
    $booking->purchased_at = $booking->created_at ?? now();
}
```

Note on the `??`: at `creating` time Eloquent has not yet run `updateTimestamps()`, so
`created_at` is normally `null` and this resolves to `now()`. The fallback is still meaningful —
a seeder or importer that explicitly backdates `created_at` gets a matching `purchased_at` for
free.

### Step 3 — Standard bookings

`BookingService::createFromPackage()` sets `'purchased_at' => now()` explicitly. The hook makes
this redundant, but explicit beats implicit at the call site that represents a real sale.

### Step 4 — Historical backfill

`HistoricalBackfillService` sets `purchased_at` to the admin-entered paper date.

### Step 5 — Query migration

| Location | Method | Line | Action |
|---|---|---|---|
| `BookingEloquentRepository` | `getRevenueByCurrency()` | `:26-27` | `created_at` -> `purchased_at` |
| `BookingEloquentRepository` | `getRevenueWithExchangeSnapshot()` | `:52-53` | `created_at` -> `purchased_at` |
| `BookingEloquentRepository` | `getTotalCount()` | `:68-69` | `created_at` -> `purchased_at` |
| `BookingEloquentRepository` | **`getTotalRevenueByCurrency()`** | `:222-223` | `created_at` -> `purchased_at` |
| `BookingEloquentRepository` | `getRevenueByPackage()` | `:100-112` | **No change** — verified: grouping only, no date filter |
| `DailyBalanceService` | date window | `:70` | inherits the repo change; no local date filter of its own |

## Corrections applied to the drafted decision

### C1 — Two sites missing from the drafted file list

**`getTotalRevenueByCurrency()` (`BookingEloquentRepository:216-225`)** filters `created_at` and
was not listed. It must migrate with the rest, or per-currency revenue totals silently diverge
from `getRevenueByCurrency()`.

**`ExchangeRateSnapshotService::getHistoricalRate()` (`:28-32`)** also filters
`bookings.created_at` — and it must **not** migrate. See C2.

Verified complete: `grep` over `app/` returns no other `bookings` date filter, and **no raw
inserts bypass the model hook** (`DB::table('bookings')->insert`, `Booking::insert(` — zero hits),
so Step 2 covers every write path that exists today.

### C2 — `ExchangeRateSnapshotService`: migrate the Booking branch, and exclude backfills

```php
// app/Services/Finance/ExchangeRateSnapshotService.php:28-32
$snapshot = Booking::where('currency_id', $currencyId)
    ->whereDate('created_at', '<=', $asOfDate)
    ->whereNotNull('exchange_rate_snapshot')
    ->orderByDesc('created_at')
    ->value('exchange_rate_snapshot');
```

It infers *"what was the exchange rate on date X"* from the newest snapshot at or before X, then
falls through to two more sources. **The other two branches of this same method already use
business dates:**

```php
MerchandiseOrder::…->whereDate('ordered_at',   '<=', $asOfDate)   // :38-41
Refund::…         ->whereDate('refunded_at',  '<=', $asOfDate)   // :48-51
```

Bookings uses `created_at` only because it had no business date to use. Inside one method, two
branches key on business dates and one on an audit date.

There **is** a real hazard in migrating it: a backfill carries today's rate by
[D-A03](D-A03-exchange-rate-override.md) default while its `purchased_at` sits months back, so it
would report today's rate as that period's historical rate.

But the column choice is not what fixes that — the **exclusion** is:

```php
->where('source_type', '!=', BookingSourceTypeEnum::HISTORICAL_BACKFILL->value)
```

Once backfills are excluded, every remaining booking has `purchased_at == created_at` by
construction (the Step 1 `UPDATE` for old rows, the Step 2 hook for new ones). The two columns are
then behaviourally identical here, so consistency with the sibling branches wins.

**Ruling for this service:** migrate the Booking branch to `purchased_at` **and** exclude
`source_type = historical_backfill`. An admin-overridden historical rate is lost as an inference
source — an acceptable trade, given the two fallbacks that follow in the same method.

### C3 — Step 6 now contradicts Step 5

The drafted Step 6 keeps Phase 1.5's `source_type != 'historical_backfill'` exclusion on the
daily balance, reasoning that "no cash physically moved today".

**Adopting `purchased_at` already achieves that, more precisely:**

| Case | `purchased_at` alone | With the exclusion also applied |
|---|---|---|
| Package bought 2 months ago, entered today | Correctly absent from today | Absent — same result |
| Package bought **today**, entered today from a paper slip | Correctly **present** in today | **Wrongly hidden** |

Once dates are keyed on `purchased_at`, the `source_type` exclusion stops being a safety net and
becomes a bug: it hides same-day paper sales that genuinely belong in today's drawer.

**Ruling: drop the `source_type` exclusion from the revenue queries.** `purchased_at` subsumes
it. This reverses one part of the original [Phase 1.5](../plan/phase-1.5-revenue-isolation.md)
scope, which is now rewritten around the date migration instead.

`source_type = historical_backfill` remains valuable for auditing and for filtering reports on
demand — it simply stops being a hard filter in the revenue path.

## This removes the last asymmetry — it does not create one

**Every other financial entity already separates business date from audit date.** `bookings` was
the sole outlier:

| Entity | Business-date column | Queries already use it |
|---|---|---|
| `merchandise_orders` | `ordered_at` — `useCurrent()`, **not nullable**, indexed `idx_order_date` | Yes — `MerchandiseOrderEloquentRepository:43,69,85,102` |
| `club_expenses` | `expense_date` — `date`, indexed | Yes — `ClubExpenseEloquentRepository:23,38` |
| `refunds` | `refunded_at` — `timestamp` | Yes — `RefundEloquentRepository:24-25` |
| `bookings` | **none — fell back to `created_at`** | — |

So D-A13 is not introducing a convention. It is bringing the last straggler into one the project
already follows everywhere else.

Two consequences:

**No work is needed for merchandise revenue.** It already keys on `ordered_at`. Adding a
`purchased_at` column there would duplicate a column that exists, is indexed, and is already used
by all four of its query sites.

**The naming is right.** The house pattern is a per-entity semantic name — `ordered_at`,
`expense_date`, `refunded_at` — not one uniform column name. `purchased_at` on `bookings` fits it.

## Hardening — mirror `merchandise_orders.ordered_at`

Left nullable, `where('purchased_at', '>=', …)` silently drops `NULL` rows, because a NULL
comparison is never true. Today that is safe — the `UPDATE` clears the backlog and the Step 2 hook
covers every write path — but a future path that bypasses Eloquent would quietly delete revenue
from every report.

The sibling column already solves this, and is the pattern to copy:

```php
// merchandise_orders
$table->timestamp('ordered_at')->useCurrent();   // NOT NULL, DB-level default
```

So after the `UPDATE`:

```php
$table->timestamp('purchased_at')->nullable(false)->useCurrent()->change();
```

`useCurrent()` makes a bypassing insert default to `now()` — correct, since any non-backfill
insert is a real-time sale — instead of writing `NULL` and vanishing from the books. Backfill
always passes an explicit value, which wins over the default. Laravel 12 changes columns natively;
no `doctrine/dbal` required.

**Recommended** — it brings `purchased_at` to exact parity with `ordered_at`.

## Result

- `created_at` = when the row was written. Audit, logs, tracing, exchange-rate observation.
- `purchased_at` = when the customer bought. All financial reporting.
- Revenue lands in the correct business period regardless of when it was typed in.

## Plan impact

| Phase | Change |
|---|---|
| [Phase 1](../plan/phase-1-schema-enum.md) | Migration gains the `UPDATE`, the optional `NOT NULL` tightening, and the model hook |
| [Phase 1.5](../plan/phase-1.5-revenue-isolation.md) | Rewritten: from "exclude backfills" to "migrate all date filters to `purchased_at`" (4 methods), plus the `ExchangeRateSnapshotService` carve-out |
| [Phase 2](../plan/phase-2-domain-core.md) | `BookingService::createFromPackage()` sets `purchased_at = now()` |
| [Phase 7](../plan/phase-7-tests.md) | `it_reports_revenue_by_purchased_at_not_created_at`; same-day backfill appears in today's balance; `getHistoricalRate` ignores backfills |
