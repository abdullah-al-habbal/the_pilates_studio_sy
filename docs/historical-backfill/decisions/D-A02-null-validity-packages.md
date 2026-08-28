# D-A02 — Null-validity packages: hard rejection

| | |
|---|---|
| **Motivating finding** | [A02](../findings/A02-null-validity-packages.md) |
| **Consistent with** | [D-A01](D-A01-active-booking-conflict.md) |
| **Date** | 2026-08-27 |
| **Status** | Accepted |

## Ruling

**Historical backfill is strictly prohibited for packages with no defined validity period.**
No exceptions, no workarounds, no injected expiry dates.

## Rationale

- `validity_days = null` means **no expiry** — the migration comment states it outright:
  `null means no expiry`.
- Backfilled with `remaining_credits > 0`, such a booking becomes `ACTIVE` with
  `expires_at = null` and stays active **forever**. That either collides with
  `unique_active_booking_per_user` today, or permanently blocks the client from ever buying
  another package.
- We will not falsify data by injecting an artificial expiry (`purchased_at + 365 days`) —
  that silently redefines the package contract.
- We will not force `attended + missed = total_credits` — the admin may lack exact paper
  records for old unlimited packages, and forcing a guess is worse than rejecting cleanly.
- Consistent with [D-A01](D-A01-active-booking-conflict.md): when the data shape creates
  unresolvable ambiguity, reject with a clear message rather than bend the system.

## Correction applied: the guard is `> 0`, not `=== null`

The drafted ruling checked `$package->validity_days === null`. **That leaves `validity_days = 0`
open, and 0 behaves identically to null throughout this codebase.**

`0` is reachable — both package endpoints accept it:

```php
// app/Http/Requests/Admin/Operations/CreatePackageRequest.php:21
// app/Http/Requests/Admin/Operations/UpdatePackageRequest.php:21
'validity_days' => ['nullable', 'integer', 'min:0'],
```

And every consumer treats `0` as "no expiry":

| Location | Test | Result for `0` |
|---|---|---|
| `app/Models/Booking.php:69` | `$package->validity_days > 0` | no `expires_at` set |
| `app/Handlers/Admin/Operations/AssignPackageHandler.php:41` | `$package->validity_days ? … : null` | `expires_at = null` |
| `app/Handlers/Booking/CreateBookingFromPackageHandler.php:17` | falsy check | `expires_at = null` |
| `app/Handlers/User/CreateBookingFromPackageHandler.php:20` | falsy check | `expires_at = null` |
| `app/Filament/Admin/Resources/Packages/Schemas/PackageInfolist.php:54` | falsy check | **displays "Unlimited"** |

So an admin can create a `validity_days = 0` package through the Operations Hub, sees it
labelled "Unlimited" in the admin panel, and it produces exactly the permanent-`ACTIVE` booking
this decision exists to prevent — while sailing past a `=== null` check.

**The guard must mirror `Booking::booted()`:** reject unless `validity_days > 0`.

## Behaviour

```php
// FIRST rule in HistoricalBackfillValidatorService — before price, before A1
if (! ($package->validity_days > 0)) {
    throw ValidationException::withMessages([
        'package_id' => __('dashboard.operations_ui.historical_backfill.error_null_validity_package'),
    ]);
}
```

`! (x > 0)` is deliberate: it is true for `null` and for `0`, and reads as the exact negation of
the `Booking::booted()` condition it must stay in lockstep with.

## Allowed outcomes

| `validity_days` | `remaining_credits` | Allowed | Resulting status |
|---|---|---|---|
| `null` | any | **Reject** | — |
| `0` | any | **Reject** | — |
| `> 0` | any | Proceed to D-A01 logic | `ACTIVE` / `EXPIRED` / `EXHAUSTED` |

The exclusion is **categorical and checked before counts.** Even where the studio holds exact
paper records giving `attended + missed = total_credits` — which would yield
`remaining_credits = 0`, status `EXHAUSTED`, and a harmless `NULL` in `active_user_id` — the
validator still rejects at the package level. This is intentional: the rule stays simple,
absolute, and impossible to misconfigure.

## Rejection message

Key: `operations_ui.historical_backfill.error_null_validity_package` — no placeholders.

**English**

> The selected package has no expiry date (unlimited validity). Historical backfill is only
> allowed for packages with a defined validity period. Please select a different package or
> contact technical support.

**Arabic**

> الباقة المختارة لا تحتوي على فترة صلاحية محددة (صلاحية مفتوحة). يُسمح بإدخال البيانات التاريخية فقط للباقات ذات فترة الصلاحية المحددة. الرجاء اختيار باقة أخرى أو التواصل مع الفريق التقني.

## Cross-references

- **Supersedes** any null-validity consideration in [D-A01](D-A01-active-booking-conflict.md).
  D-A01's conflict logic now only ever sees packages that already passed this gate.
- **Settles [D-A01 §C2](D-A01-active-booking-conflict.md)** — the `addDays(null)` `TypeError`.
  With this gate in place the service can never reach `addDays()` with `null` or `0`. The
  ternary in D-A01's reference implementation becomes belt-and-braces rather than load-bearing;
  keep it.

## Plan impact

| Phase | Change |
|---|---|
| [Phase 2](../plan/phase-2-domain-core.md) | `validity_days > 0` guard becomes the **first** validation rule — before price validation, before the D-A01 terminal-status check |
| [Phase 6](../plan/phase-6-i18n.md) | Add `error_null_validity_package` to EN + AR |
| [Phase 7](../plan/phase-7-tests.md) | Add `it_rejects_backfill_for_null_validity_package`, plus a `validity_days = 0` variant |
