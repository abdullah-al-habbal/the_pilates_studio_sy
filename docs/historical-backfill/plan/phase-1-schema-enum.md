# Phase 1 — Schema + enum

**Implements:** [D-A05](../decisions/D-A05-purchased-at-column.md), [D-A13](../decisions/D-A13-global-purchased-at-adoption.md).

## Steps

### 1.1 Schema — `purchased_at`

> **Consolidated 2026-08-27.** The owner authorised `migrate:fresh --seed`, so the column was
> folded directly into `database/migrations/2024_01_01_000005_create_bookings_table.php` and the
> standalone `add_purchased_at_to_bookings_table` migration was deleted. The three-step dance
> below (nullable -> `UPDATE` -> tighten) is therefore **historical context only** — a single
> `NOT NULL` + `useCurrent()` column declaration replaces it. The reasoning still applies to any
> environment that cannot be rebuilt from scratch.

```php
$table->timestamp('purchased_at')->nullable()->after('expires_at');
$table->index(['user_id', 'purchased_at']);
```

Then, in the same migration, backfill every existing row
([D-A13](../decisions/D-A13-global-purchased-at-adoption.md)):

```php
DB::statement('UPDATE bookings SET purchased_at = created_at WHERE purchased_at IS NULL');
```

The raw statement covers soft-deleted rows too — historical revenue must not vanish because a
booking was later soft-deleted.

**Recommended hardening.** After the `UPDATE`, bring the column to exact parity with its
sibling `merchandise_orders.ordered_at` (`useCurrent()`, not nullable):

```php
$table->timestamp('purchased_at')->nullable(false)->useCurrent()->change();
```

Left nullable, `where('purchased_at', …)` never matches `NULL`, so a future write path that
bypasses Eloquent would silently drop revenue from every report. With this, it defaults to `now()`
instead — correct, since any non-backfill insert is a real-time sale. Backfill always passes an
explicit value, which wins. Laravel 12 changes columns natively; no `doctrine/dbal` required.

Rationale in [D-A05](../decisions/D-A05-purchased-at-column.md): expiry math needs no schema
change (`Booking::booted()` honours a passed-in `expires_at`), but the historical purchase date
would otherwise be stored nowhere. `created_at` stays the true data-entry timestamp.

The `creating` hook reads `created_at`, never `purchased_at`, and skips entirely when
`expires_at` is already supplied — so this column changes no existing behaviour.

### 1.2 Enum — new source type

`app/Enums/BookingSourceTypeEnum.php`

```php
case HISTORICAL_BACKFILL = 'historical_backfill';
```

Safe per [A06](../findings/A06-source-type-extension.md): six references app-wide, zero
exhaustive `match()`, zero report filters.

### 1.3 Model

`app/Models/Booking.php`

- `$fillable` += `'purchased_at'`
- `casts()` += `'purchased_at' => 'datetime'`
- inside the existing `static::creating`, default the business date
  ([D-A13](../decisions/D-A13-global-purchased-at-adoption.md)):

```php
if (! $booking->purchased_at) {
    $booking->purchased_at = $booking->created_at ?? now();
}
```

At `creating` time Eloquent has not yet run `updateTimestamps()`, so this normally resolves to
`now()`. The `??` still earns its place: a seeder or importer that explicitly backdates
`created_at` gets a matching `purchased_at` for free.

Verified safe: no code path inserts bookings outside Eloquent (`DB::table('bookings')->insert`,
`Booking::insert(` — zero hits), so this hook covers every write in the codebase today.

## Out of scope

The `source_type` column comment (`'standard | freeze_origin | freeze_resume'`) goes stale.
It is documentation only and altering it requires `doctrine/dbal`. Leave it.

## Done when

- Migration runs clean up and down on `pilates_studio_test_db`.
- Every pre-existing booking has `purchased_at = created_at`; no row is left `NULL`.
- `expires_at` on existing rows is unchanged.
- `BookingSourceTypeEnum::HISTORICAL_BACKFILL` round-trips through the cast.
