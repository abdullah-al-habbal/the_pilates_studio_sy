# Reports Specification

## Filament Business Reports

**URL:** `/admin/reports` (Filament auto-discovered)  
**Class:** `App\Filament\Admin\Pages\Reports`  
**View:** `resources/views/filament/admin/pages/reports.blade.php`

### Period filters

| Tab | Date inputs | Financial data range |
|-----|-------------|----------------------|
| daily | `dailyDate` | That calendar day |
| monthly | `month` (YYYY-MM) | Full month |
| yearly | `year` | Full year |
| custom | `customStart`, `customEnd` | Inclusive range |

### Sections

1. Currency filter checkboxes (`selectedCurrencies`)
2. **Convert to base** toggle — uses `ExchangeRateSnapshotService` + disclaimer
3. Per-currency financial summary (revenue, packages, merch, expenses, refunds, true balance)
4. Stats: total bookings, total merchandise orders (uses period range)
5. Popular classes (top 5, period range)
6. Top merchandise (top 5, per-currency revenue)

### Backend

- `DailyBalanceService::getSummaryForRange($start, $end, $currencies, $convertToBase)`
- Livewire caches (`$_stats`, `$_classes`, `$_merch`) cleared on period/date updates

## Operations finance tab (subset)

`GET /admin/operations/finance/daily` — single-day only; same per-currency shape as reports daily mode.

## Acceptance

- **Given** monthly tab selected, **when** viewing financial summary, **then** totals aggregate entire month.
- **Given** custom range Mar 1–Mar 15, **when** viewing summary, **then** totals cover only those days.
- **Given** period changed, **when** viewing popular classes, **then** data refreshes (no stale cache).
