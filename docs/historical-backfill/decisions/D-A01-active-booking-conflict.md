# D-A01 — Active-booking conflict: hard rejection with a clear message

| | |
|---|---|
| **Motivating finding** | [A01](../findings/A01-active-booking-constraint.md) |
| **Also informed by** | [A02](../findings/A02-null-validity-packages.md), [A04](../findings/A04-no-expiry-scheduler.md) |
| **Date** | 2026-08-27 |
| **Status** | Accepted |

## Ruling

**Any historical backfill that would result in an `ACTIVE` booking for a user who already has
an active booking is rejected entirely.** No workarounds, no forced status changes, no
automatic cancellations.

## Rationale

- `unique_active_booking_per_user` is a database-level generated column + unique index, not
  application logic. It cannot be bypassed without breaking core system assumptions
  (scheduling, mobile app, notifications).
- We will not falsify data — e.g. forcing `EXPIRED` on a still-valid package — to squeeze
  records in.
- The conflict is expected to be rare for historical paper data. When it happens, the studio
  manager contacts us and we resolve case-by-case.

## Allowed outcomes

| Scenario | Terminal status | `active_user_id` | Allowed |
|---|---|---|---|
| Old package expired (`expires_at < now()`) | `EXPIRED` | `NULL` | Yes |
| Old package fully used (`remaining = 0`) | `EXHAUSTED` | `NULL` | Yes |
| No current active booking + package still valid | `ACTIVE` | `user_id` | Yes |
| Current active booking exists + package still valid | — | — | **Reject** |

Where both apply — fully used *and* expired — `EXHAUSTED` wins. It is the more informative
label, and `active_user_id` resolves to `NULL` either way.

## Corrections applied to the drafted snippet

Three defects were found while verifying the draft against the codebase.

### C1 — `$user->hasActiveCreditBooking()` does not exist, and every existing helper is the wrong one

| Symbol | Location | Filters expiry? | Matches the DB column? |
|---|---|---|---|
| `BookingEloquentRepository::userHasActiveCreditBooking(int)` | `:73` | **No** | **Yes — correct predicate** |
| `BookingEloquentRepository::existsActiveWithCredits(int)` | `:179` | Yes | No — leaky |
| `BookingService::assertNoActiveBooking(User)` | `BookingService.php:56` | Yes (via `:179`) | No — leaky |
| `User::activeCreditBooking(): HasOne` | `User.php:233` | Yes | No — leaky |
| `User::hasActiveBooking` attribute | `User.php:167` | Yes (via relation) | No — leaky |

This matters because of [A04](../findings/A04-no-expiry-scheduler.md). A stale booking —
`status = active`, `remaining_credits > 0`, `expires_at` in the past, which is routine since no
expiry cron exists — is **invisible** to every expiry-filtered helper but **still occupies
`active_user_id`**. Using `assertNoActiveBooking()` would pass validation and then hit a raw
1062, defeating the requirement that the validator never lets the database see this conflict.

The guard must use the **unfiltered** predicate: `status = active AND remaining_credits > 0`,
no expiry clause, exactly matching the generated column.

> **Pre-existing bug, out of scope — log separately.** `createFromPackage()` →
> `assertNoActiveBooking()` → `existsActiveWithCredits()` carries the identical leak, so the
> standard assign-package flow can already raise a raw 1062 when a client holds a stale active
> booking. Not introduced by this feature.

### C2 — `addDays($package->validity_days)` throws on null-validity packages

`packages.validity_days` is nullable and this codebase runs `declare(strict_types=1)`, so
`Carbon::addDays(null)` is a `TypeError`. Reuse the `> 0` guard already present in
`Booking::booted()` (`app/Models/Booking.php:63-75`).

**Resolved by [D-A02](D-A02-null-validity-packages.md)** — null- and zero-validity packages are
rejected at the gate, so the service can never reach `addDays()` with `null` or `0`. The
ternary below is now belt-and-braces rather than load-bearing; keep it.

### C3 — SQLSTATE `23000` is too broad for the safety net

`23000` covers *all* integrity constraint violations, foreign keys included. Catching it
blindly would report a genuine FK bug to the admin as "this client has an active booking".
Narrow to driver code `1062` **and** an index-name match.

## Reference implementation

```php
// 1. Terminal status — mirrors the Booking::booted() null-validity guard (C2)
$remainingCredits = $package->total_credits - $attendedCount - $missedCount;

$expiresAt = $package->validity_days > 0
    ? $purchasedAt->copy()->addDays($package->validity_days)
    : null;

$terminalStatus = match (true) {
    $remainingCredits <= 0                      => BookingStatusEnum::EXHAUSTED,
    $expiresAt !== null && $expiresAt->isPast() => BookingStatusEnum::EXPIRED,
    default                                     => BookingStatusEnum::ACTIVE,
};

// 2. Conflict guard — only when the result would occupy active_user_id.
//    Unfiltered predicate so stale active bookings are caught (C1).
if ($terminalStatus === BookingStatusEnum::ACTIVE) {
    $conflict = $this->bookingRepo->findBlockingActiveBooking($user->id);

    if ($conflict !== null) {
        throw ValidationException::withMessages([
            'package_id' => __('dashboard.operations_ui.historical_backfill.error_active_booking_conflict', [
                'client_name'       => $user->fullname,
                'package_name'      => $conflict->package->name,
                'remaining_credits' => $conflict->remaining_credits,
            ]),
        ]);
    }
}
```

```php
// 3. Safety net inside HistoricalBackfillService — should be unreachable (C3)
} catch (QueryException $e) {
    if (($e->errorInfo[1] ?? null) === 1062
        && str_contains($e->getMessage(), 'unique_active_booking_per_user')) {
        throw ValidationException::withMessages([
            'package_id' => __('dashboard.operations_ui.historical_backfill.error_active_booking_conflict', [...]),
        ]);
    }

    throw $e;   // anything else propagates untouched
}
```

## Rejection message

Key: `operations_ui.historical_backfill.error_active_booking_conflict`
Placeholders: `:client_name`, `:package_name`, `:remaining_credits`

**English**

> Cannot add historical booking for **{client_name}** because they currently have an active
> booking (**{current_package_name}** — {remaining_credits} sessions remaining).
>
> Available options:
> • Complete or refund the current booking first.
> • Enter historical data with zero remaining credits (attended + missed = total).
> • Contact technical support if this is an exceptional case.

**Arabic**

> ⚠️ لا يمكن إضافة الحجز التاريخي للعميل **{client_name}** لأنه يملك حجزاً نشطاً حالياً (**{current_package_name}** — بقي {remaining_credits} جلسات).
>
> الخيارات المتاحة:
> • أنهِ الحجز الحالي أولاً (استنفاذ أو استرداد).
> • أدخل البيانات التاريخية بدون جلسات متبقية (حضور + غياب = إجمالي الباقة).
> • تواصل مع الفريق التقني إذا كانت الحالة استثنائية.

**Rendering note.** The message is multi-line with bullets. `OperationsUI.toast()` writes into
HTML, where `\n` collapses. Either apply `white-space: pre-line` to the toast body, or split
into a `title` key plus an `options[]` array rendered as a list. Decided in
[Phase 5](../plan/phase-5-frontend.md).

## Plan impact

| Phase | Change |
|---|---|
| [Phase 2](../plan/phase-2-domain-core.md) | Add `findBlockingActiveBooking()`; terminal-status check runs **before** any write and replaces `assertNoActiveBooking()`; narrowed 1062 safety net |
| [Phase 6](../plan/phase-6-i18n.md) | Add `error_active_booking_conflict` to EN + AR with three placeholders |
| [Phase 7](../plan/phase-7-tests.md) | Add `it_rejects_backfill_when_user_has_active_booking_and_old_package_is_still_valid`, plus a stale-active-booking variant covering C1 |
