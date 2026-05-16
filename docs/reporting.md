# Financial Reporting Guide

## Interpreting Financial Reports

The Studio Operations Platform uses a multi-currency financial model (Option B). This means that while base prices are defined in a single base currency (e.g., USD), actual transactions can occur in any configured currency (e.g., SYP).

Because of this, the financial reports provide multiple ways to view revenue data.

### Per-Currency View
This is the default and most accurate view. It strictly separates revenue by the exact currency the transaction was paid in.
- **Why it matters**: It prevents mixing apples and oranges (e.g., adding USD and SYP together, which makes no sense mathematically).
- **How to read it**: The report generates separate summary grids for each currency. If 5 packages were sold in USD and 2 in SYP, you will see a USD Revenue Section and a SYP Revenue Section.

### Base-Converted View (Toggle)
In the Filament Reports page, there is a "Convert all to Base Currency" toggle.
- **How it works**: When enabled, the system uses the `exchange_rate_snapshot` saved at the time of each historical transaction to convert the paid amount back into the base currency.
- **Why it matters**: This provides a unified, single-number view of your total revenue.
- **Disclaimer**: Because exchange rates fluctuate, the "Base Converted" total is an approximation of value at the time the transaction occurred, not necessarily the exact liquid value you hold today if you haven't exchanged the funds.
  - The UI displays a warning disclaimer when this toggle is active to ensure the admin understands they are looking at historically converted approximations.

## Filament Reports Implementation

Under the hood, the Reports page `Reports.php` handles these two distinct modes.
- `buildCurrencySections()` iterates over the typed `CurrencySummaryData` DTO collection.
- A `base_conversion_applied` flag dictates whether the UI renders a single unified currency grid or separated currency sections.

## Common Reporting Mistakes

❌ **Don't** assume the Base-Converted view represents your current bank balance. It represents the *historical value* of those sales.
✅ **Do** use the Per-Currency view when reconciling actual cash drawers or bank accounts, as this represents the exact denominations collected.
