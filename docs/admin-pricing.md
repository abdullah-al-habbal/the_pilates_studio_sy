# Admin Pricing Guide — Option B Architecture

## Creating Packages with Base Price

1. Navigate to **Packages** in Filament admin
2. Click **+ New Package**
3. Fill in:
    - **Name**: Enter package name (supports translations)
    - **Sessions**: Number of credits included
    - **Validity**: Optional expiration in days
4. **Pricing Section**:
    - Select **Base Currency** (pre-filled from `config/currency.base_currency`)
    - Enter **Base Price**
    - ⚠️ Do NOT enter prices for other currencies — they are computed automatically
5. Save package

## How Pricing Works

- Admin sets ONE base price in base currency (e.g., USD)
- When client pays in another currency (e.g., SYP):

```
final_amount = base_price × exchange_rate_at_transaction_time
```

- Example:
  - Base price: `20000` (=$200.00 USD)
  - SYP exchange rate: `13000.0`
  - SYP divisor: `1` (0 decimal places)
  - Computed amount: `(20000 / 100) × 13000 × 1 = 2,600,000` SYP

## Viewing Prices in Operations Dashboard

- Package cards show price in selected currency
- Amount field is **readonly** — computed server-side
- Exchange rate tooltip shows: `1 USD = 13,000.00 SYP (updated 2m ago)`

## Updating Exchange Rates

1. Navigate to **Currencies** in Filament
2. Edit currency record
3. Update **Exchange Rate** field
4. Save → cache auto-busts, new transactions use updated rate
5. ⚠️ Historical transactions retain their original `exchange_rate_snapshot`

## Common Pitfalls

❌ **Don't** create multiple `Price` rows per package for different currencies
✅ **Do** rely on `PricingService` for automatic conversion

❌ **Don't** edit `paid_amount` directly on bookings
✅ **Do** let handlers compute amounts via `PricingService`

❌ **Don't** aggregate `SUM(paid_amount)` across currencies
✅ **Do** use per-currency reports or base-converted totals with disclaimer
