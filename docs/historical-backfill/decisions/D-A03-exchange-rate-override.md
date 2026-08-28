# D-A03 — Exchange rate: optional admin override, current-rate default

| | |
|---|---|
| **Motivating finding** | PRD §8.1 / §10 — historical rate is approximated by today's rate |
| **Date** | 2026-08-27 |
| **Status** | Accepted |

## Ruling

**The backfill form exposes an optional `exchange_rate_snapshot` field, pre-filled with the
current rate.**

- Admin leaves it untouched -> today's rate is used (existing behaviour).
- Admin edits it -> their value is used, and the override is logged with both rates.

## Rationale

- Using today's rate blindly loses accuracy whenever the admin *does* hold the historical invoice.
- Forcing manual entry burdens every backfill, including the common case where the old rate is
  simply unknown.
- The hybrid is accurate when possible, convenient when not, and fully auditable either way.

## Model compatibility — verified, no change required

`Booking::booted()` auto-computes the rate only when the attribute is falsy:

```php
// app/Models/Booking.php:77-85  (static::saving — note: saving, not creating)
static::saving(function (Booking $booking) {
    if (! $booking->exchange_rate_snapshot) {
        $currencyId = $booking->currency_id
            ?? app(CurrencyService::class)->getBaseCurrency()->id;
        $booking->exchange_rate_snapshot = app(PricingService::class)
            ->getExchangeRateForSnapshot($currencyId);
    }
});
```

Passing an explicit non-zero value from the handler therefore suppresses auto-computation
**without touching the model**. Confirmed non-breaking.

## Validation

```php
'exchange_rate_snapshot' => ['nullable', 'numeric', 'min:0.000001', 'max:999999'],
```

**`min:0.000001` is load-bearing, not a sanity bound.** The hook above tests falsiness, and
`0` is falsy. A stored `0` would make `static::saving` recompute the rate on *every subsequent
save* of that booking, silently replacing the admin's historical rate with today's. The minimum
must exclude zero.

Column is `decimal(12,6)` (`create_bookings_table`), so:

- `max:999999` sits just inside the 6-digit integer part — conservative and correct.
- Values with more than six decimal places are **silently rounded by MySQL**. Acceptable;
  worth a hint in the UI.

## Corrections applied to the drafted snippets

### C1 — `$currentRate` is never assigned

The drafted audit block logs `'current_rate' => $currentRate`, but the drafted ternary only ever
computes *one* rate. In the override branch `$currentRate` is undefined.

Resolve the current rate **unconditionally**, then decide which value to use. One extra
`PricingService` call, and it makes the override log genuinely useful — it records the delta,
not just the entered number.

### C2 — Redundant presence check (simplification, not a bug)

`$request->has('x') && $request->x !== null` is equivalent to `$request->input('x') !== null`,
because `$request->x` routes through `__get` to `input()`. Keep the semantics, drop the
duplication.

## Reference implementation

```php
// Handler — resolve both rates, then choose.
$currentRate = app(PricingService::class)->getExchangeRateForSnapshot($currencyId);
$providedRate = $request->input('exchange_rate_snapshot');
$isOverride = $providedRate !== null;

$rate = $isOverride ? (float) $providedRate : $currentRate;
```

```php
// Audit — both branches, per PRD §8.
if ($isOverride) {
    Log::info('Historical backfill used admin-provided exchange rate.', [
        'admin_id'      => auth()->id(),
        'user_id'       => $userId,
        'provided_rate' => $rate,
        'current_rate'  => $currentRate,
    ]);
} else {
    Log::info('Historical backfill used current exchange rate for old transaction.', [
        'admin_id' => auth()->id(),
        'user_id'  => $userId,
        'rate'     => $rate,
    ]);
}
```

`$rate` is then passed explicitly into the booking payload, suppressing the `saving` hook.

## Frontend

Financial-details step, beside the currency selector:

| Property | Value |
|---|---|
| Label | "Exchange Rate" |
| Default | `PricingService::getExchangeRateForSnapshot($currencyId)` |
| Hint | *"Leave as-is for current rate, or enter the historical rate if known."* |
| Emphasis | De-emphasised — the admin should feel free to ignore it |
| Recompute | Re-fill the default when the selected currency changes, unless the admin has already edited the field |

## Plan impact

| Phase | Change |
|---|---|
| [Phase 2](../plan/phase-2-domain-core.md) | Validator accepts the optional rate; service takes it as a parameter |
| [Phase 4](../plan/phase-4-submit-endpoint.md) | Request rule; handler resolves both rates per C1 and logs both branches |
| [Phase 5](../plan/phase-5-frontend.md) | Pre-filled input in the financial-details step |
| [Phase 6](../plan/phase-6-i18n.md) | Label, hint, validation-error keys |
| [Phase 7](../plan/phase-7-tests.md) | `it_uses_admin_provided_exchange_rate_when_present`, `it_uses_current_rate_when_exchange_rate_snapshot_is_null` |
