# Phase 6 — Localization

## Steps

### 6.1 Add the key namespace to both files

- `resources/lang/en/dashboard.php`
- `resources/lang/ar/dashboard.php`

under `operations_ui.historical_backfill.*`.

Note the path: `resources/lang/`, **not** the top-level `lang/` (which holds only `landing.php`
and `notifications.php`).

### 6.2 Required keys

Everything the UI renders: step titles, field labels, placeholders, hints, the live arithmetic
summary, the selection counter, success and error toasts.

Plus the two decided rejection messages. EN and AR copy is written out in each decision file.

| Key | Placeholders | Source |
|---|---|---|
| `…historical_backfill.error_active_booking_conflict` | `:client_name`, `:package_name`, `:remaining_credits` | [D-A01](../decisions/D-A01-active-booking-conflict.md) |
| `…historical_backfill.error_null_validity_package` | none | [D-A02](../decisions/D-A02-null-validity-packages.md) |

Plus the exchange-rate field from [D-A03](../decisions/D-A03-exchange-rate-override.md):
label, hint ("Leave as-is for current rate, or enter the historical rate if known"), and its
validation-error strings.

### 6.3 Parity check

`operations_ui` is currently at **exact** parity — 226 keys EN, 226 AR. Keep it.

(For context, the wider `dashboard.php` has 61 keys missing in AR, but none under
`operations_ui`. Do not add to that debt.)

Re-run the flatten-and-diff check before committing.

## Done when

- Every new key exists in both files.
- `operations_ui` parity still holds.
- No raw key strings leak into the UI in either locale.
