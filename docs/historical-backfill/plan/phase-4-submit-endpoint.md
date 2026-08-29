# Phase 4 — Submit endpoint

> **Superseded 2026-08-28.** The dedicated endpoint was merged into the assign endpoint:
> historical entries now POST to `/admin/operations/packages/{packageId}/assign`, and the
> presence of `purchased_at` is what routes them down the backfill path. `HistoricalBackfillAction`,
> `HistoricalBackfillRequest` and `HistoricalBackfillHandler` were deleted; their behaviour lives in
> `AssignPackageAction`, `AssignPackageRequest` and `AssignPackageHandler::recordHistorical()`.
> The 19 endpoint tests moved with it and grew to 26. Everything below describes the original
> shape and is kept for the reasoning, not the routes.

**Blocking on:** Phase 2.

## Steps

### 4.1 Route

`routes/web/operations/bookings.php`

```php
Route::post('/backfill', HistoricalBackfillAction::class)->name('backfill.store');
```

Full URI: `POST /admin/operations/bookings/backfill`.

### 4.2 HTTP classes

Mirror the assign-package trio exactly:

| New | Modelled on |
|---|---|
| `HistoricalBackfillAction` | `app/Http/Actions/Web/Admin/Operations/AssignPackageAction.php:21` |
| `HistoricalBackfillRequest` | `app/Http/Requests/Admin/Operations/AssignPackageRequest.php:16-23` |
| `HistoricalBackfillHandler` | `app/Handlers/Admin/Operations/AssignPackageHandler.php:24-64` |

Response envelope: `$this->created()` on success, `$this->unprocessable()` on `\Throwable` —
same as `AssignPackageAction`.

### 4.3 Exchange-rate resolution — [D-A03](../decisions/D-A03-exchange-rate-override.md)

Request rule:

```php
'exchange_rate_snapshot' => ['nullable', 'numeric', 'min:0.000001', 'max:999999'],
```

Handler — resolve **both** rates, then choose. The current rate must be computed
unconditionally so the override branch can log the delta:

```php
$currentRate  = app(PricingService::class)->getExchangeRateForSnapshot($currencyId);
$providedRate = $request->input('exchange_rate_snapshot');
$isOverride   = $providedRate !== null;

$rate = $isOverride ? (float) $providedRate : $currentRate;
```

Log both branches (admin id, client id, rate, and — on override — the current rate too).
See [D-A03 §C1](../decisions/D-A03-exchange-rate-override.md) for why the drafted single-rate
ternary left `$currentRate` undefined.

### 4.4 Idempotency

Guard in the handler, per [A11](../findings/A11-idempotency-net-new.md):

```php
if (! Cache::add("backfill:{$token}", true, 300)) {
    throw ValidationException::withMessages([...]);
}
```

`Cache::add()` is atomic, so it doubles as the lock.

**Verify the production `CACHE_STORE` before shipping.** Tests use `array` (`phpunit.xml:25`),
which is per-process and would make the guard a no-op across requests.

## Done when

- Valid payload creates exactly one booking and the expected `booking_sessions` rows.
- A replayed token is rejected without a second write.
- A D-A01 conflict returns a 422 carrying the localized message, not a 500.
- An omitted rate falls back to the current rate; a supplied rate is stored verbatim and logged with the current rate alongside it.
