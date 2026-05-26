# Operations UI Specification

**Entry:** `GET /admin/operations`  
**Layout:** `resources/views/layouts/operations.blade.php`  
**Scripts:** `public/js/operations/api.js`, `ui.js`, `main.js` (module), tab modules.

## Tabs (hash routing)

| Hash | Partial | Module |
|------|---------|--------|
| `#clients` | `tab-clients.blade.php` | `modules/clients.js`, `packages.js` |
| `#store` | `tab-store.blade.php` | `modules/store.js` |
| `#finance` | `tab-finance.blade.php` | `modules/finance.js` |

`window.OperationsCurrencies` — active currencies from Blade for formatting.

## Global components

- **Toasts:** `OperationsUI.toast(message, type)`
- **Modals:** `OperationsUI.openModal(title, html)`
- **Currency format:** `OperationsUI.formatMoney(amount, currencyId)`
- **Sidebar snapshot:** `tabs.js` → `updateGlobalStats()` via `getDailyBalance(today)`

## Clients tab

1. Search debounced → `OperationsAPI.getClients(search, page, filter)`
2. Status badge uses API field `status` (`active` | `frozen` | `deactivated`)
3. **Details modal:** load `getClientDetails`; show Unfreeze when `frozen_package` present; Freeze/Refund when `active_package` present
4. Package assign modal uses selected `currency_id`; server computes price

## Store tab

- Grid from `getStoreItems()`; quick sale with currency selector
- Price preview uses `PricingService` rates via `OperationsCurrencies` exchange rates

## Finance tab

- Date picker → `getDailyBalance(date, selectedCurrencyCodes, convertToBase)`
- Optional **Convert to base currency** toggle
- Expense form → `recordExpense`
- Revenue chart placeholder (optional future Chart.js)

## Acceptance (UI)

- **Given** client with frozen package, **when** opening details, **then** Unfreeze button is visible and works.
- **Given** client list, **when** rendered, **then** status column shows ACTIVE/FROZEN/DEACTIVATED not UNKNOWN.
- **Given** finance tab, **when** toggling base conversion, **then** balance cards show converted totals with disclaimer.
