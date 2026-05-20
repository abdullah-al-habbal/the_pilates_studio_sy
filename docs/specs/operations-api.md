# Operations API Specification

Base path: `/admin/operations`  
Middleware: `web`, `auth`, `freeze.user`, `cache.api.get` (GET responses cached 30s)

## Response envelope

```json
{
  "success": true,
  "code": "SUCCESS",
  "message": "...",
  "data": {},
  "timestamp": "2026-05-20T12:00:00.000000Z",
  "status_code": 200,
  "meta": { "pagination": { "total", "count", "per_page", "current_page", "total_pages" } }
}
```

Money fields are **integers in the currency smallest unit** (e.g. cents).

## Endpoints

### `GET /` — Operations dashboard (HTML)

- **Auth:** session required; frozen staff blocked (`freeze.user` → 403 `FROZEN_USER`)
- **Response:** Blade page `admin.operations.index`

### `GET /clients`

| Query | Type | Rules |
|-------|------|-------|
| `search` | string | optional |
| `page` | int | optional, default 1 |
| `filter` | string | optional: `best_user`, `most_active_booking`, `best_seller`, `most_attended` |

**Data item:** `{ id, fullname, phone_number, is_active, status, member_since, active_package?, frozen_package?, sessions_attended, sessions_cancelled }`

**Acceptance:** Given authenticated admin, when listing clients, then paginated JSON with `meta.pagination`.

### `GET /clients/{userId}/details`

**Data:** `{ id, fullname, phone_number, email, member_since, is_active, status, active_package?, frozen_package?, activity_snapshot, store_purchases[] }`

**Acceptance:** Given user with frozen booking, when opening details, then `frozen_package` is populated and `active_package` is null.

### Packages

| Method | Path | Body |
|--------|------|------|
| GET | `/packages` | — |
| POST | `/packages` | `{ name, total_credits, validity_days?, currency_id, amount }` |
| PUT | `/packages/{id}` | same as POST |
| DELETE | `/packages/{id}` | — |
| POST | `/packages/{id}/assign` | `{ user_id, currency_id }` — amount computed server-side |

**Assign acceptance:** Given active client, when assigning with valid currency, then booking created with `exchange_rate_snapshot`.

### Store

| Method | Path | Body |
|--------|------|------|
| GET | `/store/items` | — |
| POST | `/store/orders` | `{ customer_id, merchandise_id, quantity, currency_id }` |
| POST | `/store/walk-in-order` | `{ merchandise_id, quantity, currency_id, fullname, phone_number, email? }` |

### Finance

| Method | Path | Query / Body |
|--------|------|----------------|
| GET | `/finance/daily` | `date`, `currencies[]`, `convertToBase` (bool) |
| GET | `/finance/categories` | — |
| POST | `/finance/expenses` | `{ category_name, currency_id, amount, notes?, date? }` |

**Daily balance item:** per currency: `package_revenue`, `merchandise_revenue`, `total_revenue`, `total_expenses`, `total_refunds`, `true_balance`, optional `*_in_base` when `convertToBase=true`.

### Bookings

| Method | Path | Body |
|--------|------|------|
| POST | `/bookings/{id}/freeze` | empty |
| POST | `/bookings/{id}/unfreeze` | empty |
| POST | `/bookings/{id}/refund` | `{ amount?: int }` — omit/null = full refund |

**Refund acceptance:** Given active booking with paid amount, when refund amount exceeds paid, then 422.

## Error codes

| HTTP | When |
|------|------|
| 401 | Unauthenticated |
| 403 | Frozen staff user |
| 422 | Validation / business rule failure |
| 500 | Unhandled exception (logged) |
