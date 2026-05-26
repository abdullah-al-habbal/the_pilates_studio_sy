# Currency & Financial Rules

Architecture: **Option B — Base Price + Exchange Rate** (see `antigravity.md`).

## Storage

- All monetary amounts: **integer** per `Currency.decimal_places`
- `exchange_rate_snapshot` on `bookings`, `merchandise_orders`, `refunds` at transaction time
- Base prices in `prices` morph table (base currency only)

## Pricing

- `PricingService::calculateAmount(basePrice, targetCurrencyId)` — never use float for money
- Assign package / place order capture snapshot via `PricingService::getExchangeRateForSnapshot()`

## Refunds

- Amount ≤ original `paid_amount` (same currency)
- Refund inherits booking `exchange_rate_snapshot`

## Reporting

- **Never** sum `paid_amount` across different `currency_id` without conversion
- Per-currency sections are authoritative for cash reconciliation
- Base-converted totals use historical snapshots; show disclaimer in UI

## Data migration (Phase 6)

```bash
php artisan finance:backfill-exchange-snapshots
php artisan finance:verify-daily-balance --date=YYYY-MM-DD
```

Backfill sets snapshot from current `Currency.exchange_rate` when null (legacy rows).
