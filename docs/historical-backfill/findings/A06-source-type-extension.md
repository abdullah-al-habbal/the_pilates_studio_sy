# A06 — `source_type` is safe to extend

| | |
|---|---|
| **Verdict** | Low risk; PRD §15.2 prerequisite is cheap |
| **Impact** | Low |
| **Status** | Accepted |

## What the code does

`BookingSourceTypeEnum` has three cases (`app/Enums/BookingSourceTypeEnum.php:9-11`):
`STANDARD`, `FREEZE_ORIGIN`, `FREEZE_RESUME`.

Every reference to `source_type` app-wide:

| Location | Use |
|---|---|
| `app/Models/Booking.php:37` | `$fillable` |
| `app/Models/Booking.php:55` | cast to enum |
| `app/Services/Booking/BookingFreezeService.php:41` | write `FREEZE_ORIGIN` |
| `app/Services/Booking/BookingFreezeService.php:92` | write `FREEZE_RESUME` |
| `app/Support/Operations/BookingPackageMapper.php:24` | read for display |
| `app/Http/Resources/Admin/Operations/ClientActivePackageResource.php:17` | read for display |

## Consequence

- **Zero exhaustive `match()` or `switch` statements** on the enum — adding a case breaks nothing.
- **Zero report or finance queries filter on it** — which is itself the problem described in
  [A13](A13-revenue-contamination.md).

Adding `HISTORICAL_BACKFILL = 'historical_backfill'` is a one-line change.

The column comment in the migration (`'standard | freeze_origin | freeze_resume'`) becomes
stale. It is documentation only and requires `doctrine/dbal` to alter — leave it.
