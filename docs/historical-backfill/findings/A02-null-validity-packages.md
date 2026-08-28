# A02 — Packages with `validity_days = null` never expire

| | |
|---|---|
| **Verdict** | Gap the PRD does not address |
| **Impact** | High |
| **Status** | Decided — [D-A02](../decisions/D-A02-null-validity-packages.md) |

## What the code does

`packages.validity_days` is nullable:

```php
// database/migrations/2024_01_01_000001_create_packages_table.php
$table->unsignedSmallInteger('validity_days')->nullable()->default(null)
    ->comment('If not null and > 0, booking expires_at = created_at + validity_days. null means no expiry');
```

`Booking::booted()` mirrors that guard — it only computes `expires_at` when
`validity_days > 0` (`app/Models/Booking.php:63-75`). A booking from a null-validity package
therefore has `expires_at = NULL` forever.

## Consequence

Combined with [A01](A01-active-booking-constraint.md): a backfill of a null-validity package
with leftover credits can **never** reach a safe terminal status. It is permanently
`status = active, remaining_credits > 0`, so it permanently occupies `active_user_id`.

There is no date at which it becomes harmless.

## Options considered

1. **Forbid null-validity packages in backfill** — validation error at package selection.
2. **Force `attended + missed = total_credits`** for null-validity packages only, so
   `remaining_credits = 0` and the column resolves to `NULL`.
3. Require the admin to supply an explicit expiry date for these packages.

Option 1 was chosen — see [D-A02](../decisions/D-A02-null-validity-packages.md).

## `validity_days = 0` is the same case

`0` is reachable (`CreatePackageRequest.php:21` / `UpdatePackageRequest.php:21` allow `min:0`)
and every consumer treats it as "no expiry" — including `PackageInfolist.php:54`, which
displays it to admins as **"Unlimited"**. Any guard must test `> 0`, not `=== null`.
Detail in [D-A02](../decisions/D-A02-null-validity-packages.md).

## Secondary defect this exposes

The reference snippet drafted for D-A01 used `$purchasedAt->copy()->addDays($package->validity_days)`
unguarded. Under `declare(strict_types=1)` — used throughout this codebase — `Carbon::addDays(null)`
is a `TypeError`. Any implementation must reuse the `> 0` guard from `Booking::booted()`.
