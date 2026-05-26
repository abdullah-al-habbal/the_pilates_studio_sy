# 🤖 Antigravity Agent Context: Studio Operations System

## 🎯 Project Goal

Implement **Option B: Base Price + Exchange Rate** architecture for a Laravel 11 multi-currency fitness studio platform. Every financial transaction must capture an immutable `exchange_rate_snapshot` at execution time.

## 🏗️ Tech Stack

- **Backend**: PHP 8.2+, Laravel 11, Strict Types (`declare(strict_types=1);`)
- **Pattern**: `Action → Handler → Service → Repository → Model`
- **Validation**: Pure `FormRequest` rules only (no business logic)
- **Data**: Commands/DTOs for handler inputs; Eloquent for persistence
- **Frontend**: Blade + Vanilla ES6 JS (`public/js/operations/`), Tailwind, Filament v3
- **Database**: MySQL, relational schema, soft deletes, indexed FKs
- **Currency**: All amounts stored as (no floats for money)

## 🔑 Core Models & Relationships

```
User → hasMany → Booking, MerchandiseOrder, Refund
Package → morphMany → Price (base price only in base currency)
CenterMerchandise → morphMany → Price
Booking/MerchandiseOrder/Refund → belongsTo → Currency
Refund → morphTo → Booking | MerchandiseOrder
```

## 🚨 Critical Issues to Fix

1. `Currency.exchange_rate` exists but is **never used** in pricing logic
2. `Booking`, `MerchandiseOrder`, `Refund` lack `exchange_rate_snapshot` column → breaks audit trail
3. Hybrid pricing allows `0` amount assignments when `Price` row missing for currency
4. Financial reports aggregate `paid_amount` across currencies without conversion → meaningless totals
5. Frontend displays prices in global default currency regardless of transaction currency
6. `FormRequest` validation contains direct model queries (anti-pattern)

## ✅ Antigravity Agent Guidelines

### DO:

- Maintain `declare(strict_types=1);` on all PHP files
- Follow `Action → Handler → Service → Repository` separation
- Use `Command`/`DTO` objects for handler inputs
- Store currency amounts as `int` 
- Keep `FormRequest` validation rules pure (no DB queries)
- Modify existing migrations directly (DEV environment)
- Preserve foreign keys, indexes, soft-deletes
- Add `exchange_rate_snapshot` to financial models in Phase 1

### DON'T:

- Mix business logic in `FormRequest` or Controllers
- Use `float` for monetary values
- Create new migrations when existing ones can be safely modified (DEV)
- Aggregate `paid_amount` across different `currency_id` without conversion
- Hardcode base currency; use `config('currency.base_currency')`
- Break Filament admin or Operations UI contracts without explicit updates

## 🗺️ Implementation Roadmap (Antigravity Task List Format)

```markdown
## Phase 1: Schema & Config Foundation

- [x] Add `exchange_rate_snapshot` column to `bookings`, `merchandise_orders`, `refunds`
- [x] Create `config/currency.php` with `base_currency => 'USD'`
- [x] Update Eloquent models: add to `$fillable` and `$casts`
- [x] Update Filament Resources: display snapshot as read-only field
- [x] Run `php artisan migrate:fresh --seed` and verify schema

## Phase 2: Centralized Pricing Engine

- [x] Create `PricingService::calculateAmount(basePrice, targetCurrencyId)`
- [x] Refactor `Package::getPriceForCurrency()` to use `PricingService`
- [x] Enforce single base-currency `Price` row per package/merchandise
- [x] Update frontend to compute prices via exchange rate, not hardcoded rows

## Phase 3: Transaction Validation & Handlers

- [ ] Extract `AssignPackageRequest` validation logic to service
- [ ] Update `AssignPackageHandler` to capture `exchange_rate_snapshot`
- [ ] Add server-side refund validation: `amount ≤ original paid_amount`
- [ ] Enforce refund currency matches original transaction currency

## Phase 4: Frontend Integration

- [ ] Fix `packages.js`: compute amount dynamically, show rate tooltip
- [ ] Fix `store.js`: show live price preview in Quick Sale modal
- [ ] Fix `clients.js`: format refund amounts using transaction currency
- [ ] Remove `parseFloat` from amount handling; enforce integer payloads

## Phase 5: Financial Reporting

- [x] Deprecate cross-currency `SUM(paid_amount)` aggregations
- [x] Update `DailyBalanceService` to report per-currency or base-converted totals
- [x] Fix Filament `Reports` page: group metrics by currency
- [x] Add disclaimer when displaying base-converted totals

## Phase 6: Data Migration & Backfill

- [ ] Write script to backfill `exchange_rate_snapshot` for historical transactions
- [ ] Archive legacy multi-currency `Price` rows; keep base-currency only
- [ ] Run integrity checks: per-currency revenue matches reported totals
- [ ] Deploy behind feature flag; enable after verification

## 📁 Key File Paths
```

app/Models/
├── Booking.php, MerchandiseOrder.php, Refund.php, Package.php, CenterMerchandise.php
├── Price.php, Currency.php, User.php

app/Services/
├── Booking/, Package/, Merchandise/, Currency/, Finance/

app/Handlers/Admin/Operations/
├── AssignPackageHandler, PlaceOrderHandler, ProcessBookingRefundHandler

app/Http/Actions/Web/Admin/Operations/
├── AssignPackageAction, PlaceOrderAction, ProcessBookingRefundAction

app/Repositories/Eloquent/
├── Booking/, Merchandise/, Package/, Refund/, ClubExpense/

public/js/operations/
├── api.js, ui.js, modules/{clients.js, packages.js, store.js, finance.js}

resources/views/admin/operations/
├── index.blade.php, partials/{tab-clients, tab-store, tab-finance}.blade.php

app/Filament/Admin/Pages/Reports.php

```

## 🔄 Antigravity Workflow Notes

- Use **Task List artifact** to track phase progress; check items as completed
- Enable **browser subagent** when testing Filament UI changes
- Use **codebase semantic search** to locate all usages of `paid_amount`, `exchange_rate`, `Price`

---

_Last Updated: Phase 1 Prep | Architecture: Laravel 11 Strict Types | Financial Model: Option B (Base Price + Exchange Rate)_
```
