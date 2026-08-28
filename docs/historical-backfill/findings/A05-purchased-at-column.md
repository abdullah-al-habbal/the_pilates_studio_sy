# A05 — A `purchased_at` column is needed to retain the historical date

| | |
|---|---|
| **Verdict** | Resolves PRD §5.2 / §15.1, but not the way the PRD frames it |
| **Impact** | Medium |
| **Status** | Decided — [D-A05](../decisions/D-A05-purchased-at-column.md) |

## What the code does

`Booking::booted()` sets `expires_at` **only when it was not already supplied**, and always
snapshots the package validity:

```php
// app/Models/Booking.php:61-85  (static::creating)
if ($package_id) {
    $validity_days_snapshot = $package->validity_days;
    if ($package->validity_days > 0 && ! $expires_at) {
        $expires_at = now()->addDays($package->validity_days);
    }
}
```

## Consequence

PRD §5.2 asks for a schema decision on how `expires_at` is computed for backfilled records.
**No schema change is required for expiry math** — passing an explicit `expires_at` already
wins over the hook.

But the historical *purchase date itself* is then stored nowhere. `created_at` is the
data-entry timestamp. Back-deriving `expires_at − validity_days_snapshot` is the only route,
and it fails outright for null-validity packages ([A02](A02-null-validity-packages.md)), where
`expires_at` is `NULL`.

## Recommendation — accepted

Add a nullable `timestamp purchased_at` to `bookings`, indexed `(user_id, purchased_at)`.

- One migration, no behavioural change for existing rows.
- Makes the historical date first-class for reports and audits.
- Prerequisite for the `COALESCE(purchased_at, created_at)` reporting option in
  [A13](A13-revenue-contamination.md).
