# Route → Blade → JS Matrix

## Operations (`routes/web/operations.php`)

| Route | Blade | JS (`OperationsAPI`) |
|-------|-------|------------------------|
| GET `/admin/operations` | `admin/operations/index.blade.php` | `main.js`, tabs |
| GET `/clients` | tab-clients | `getClients` |
| GET `/clients/{id}/details` | modal (clients.js) | `getClientDetails` |
| GET/POST/PUT/DELETE `/packages*` | modals (packages.js) | `getPackages`, `createPackage`, etc. |
| GET `/store/items` | tab-store | `getStoreItems` |
| POST `/store/orders` | tab-store | `placeOrder` |
| POST `/store/walk-in-order` | tab-store | `storeWalkInOrder` |
| GET `/finance/daily` | tab-finance, quick-stats | `getDailyBalance` |
| GET `/finance/categories` | tab-finance | (datalist via categories) |
| POST `/finance/expenses` | tab-finance | `recordExpense` |
| POST `/bookings/{id}/freeze|unfreeze|refund` | clients modal | `freezeBooking`, etc. |

**Gaps (non-blocking):** `#revenue-chart-placeholder`, `#connection-status` (static).

## Scheduler (`routes/web/scheduler.php`)

| Route | Blade shell | JS (`Scheduler.api`) |
|-------|-------------|----------------------|
| GET `/admin/scheduler` | `admin/scheduler/index.blade.php` | `main.js` |
| GET `/sessions` | main partial | `getSessions` |
| GET `/sessions/{id}` | modal | `getSession` |
| GET `/users` | walk-in tab | `getUsers` |
| GET `/walkin/validate` | walk-in form | `validateField` |
| POST attendance / walkin | modal | `postAttendance`, etc. |

## Reports (Filament only)

| Surface | Backend | Frontend |
|---------|---------|----------|
| `/admin/reports` | `Reports.php` | Livewire Blade |
| Operations finance tab | `GetDailyBalanceAction` | `finance.js` |

## Mobile API (`routes/api/v1/`)

Documented in `docs/openapi/mobile-v1.yaml` — no admin Blade counterpart.
