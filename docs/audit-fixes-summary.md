# Codebase Audit & Fix Summary

**Date:** 2026-05-27
**Scope:** Full codebase analysis, documentation audit, production readiness check

---

## 1. Version Inconsistencies

All documentation files referenced outdated or conflicting versions:

| File | Before | After |
|------|--------|-------|
| `antigravity.md` | Laravel 11, Filament v3, PHP 8.4+ | Laravel 12, Filament v5, PHP 8.4+ |
| `Claude.md` | Laravel 10+, PHP 8.1+ | Laravel 12, PHP 8.4+ |

Also added missing `README.md` at project root.

---

## 2. Missing `declare(strict_types=1)`

14 Model files were missing `declare(strict_types=1)`, violating the project's stated convention:

- `AppNotification`, `ClassCategory`, `ClassImage`, `Classes`, `ClassSession`
- `Instructor`, `Language`, `MobileAppVersion`, `NotificationTemplate`
- `PushNotificationLog`, `RecurrencePattern`, `StaticPage`, `Testimonial`, `UserSetting`

---

## 3. .env.example Issues

Three problems fixed:

1. **Duplicate content** — The file was 236 lines with the entire config duplicated.
2. **Missing env vars** — `CURRENCY_BASE`, `FINANCIAL_MIN_REFUND_AMOUNT`, `FINANCIAL_ALLOW_PARTIAL_REFUNDS` were used by `config/currency.php` and `config/financial.php` but absent from `.env.example`.
3. **Wrong FCM path** — `FIREBASE_CREDENTIALS` pointed to `app/firebase/firebase-credentials.json` but the actual `.env` uses `storage/app/firebase-credentials.json`.

---

## 4. Schedule Not Running

`bootstrap/app.php` loads commands from `routes/console/console.php`, but that file was empty. The actual schedule was in `routes/console.php` (never loaded). Moved the schedule definitions to the correct file.

---

## 5. Package Pricing Enforcing Base Currency

Per the Option B architecture (Base Price + Exchange Rate), all package prices must be stored only in the base currency. Two handlers were accepting arbitrary `currency_id`:

- `CreatePackageHandler` — now resolves base currency internally, ignores client-provided currency
- `UpdatePackageHandler` — same fix

Corresponding `FormRequest` files (`CreatePackageRequest`, `UpdatePackageRequest`) updated to make `currency_id` nullable.

---

## 6. AssignPackageRequest Validation

`currency_id` was required in validation but the handler already supported falling back to base currency when null. Made the field optional.

---

## 7. Refund Snapshot Cast Bug

`ProcessBookingRefundHandler` had:
```php
'exchange_rate_snapshot' => $booking->exchange_rate_snapshot ?? ['rate' => 1, 'currency' => ...],
```
Since the model casts `exchange_rate_snapshot` to `float`, it could never be null — the array fallback was dead code. Fixed to properly check for null/zero before falling back to `$booking->currency->exchange_rate`.

---

## 8. Hardcoded Walk-in Password

`BookingSessionService::createWalkInUser()` used `bcrypt('12345678')` as fallback password, and `StoreWalkInOrderHandler` used `'pilates'`. Changed to `\Str::random(16)`.

---

## 9. Documentation Accuracy

- `antigravity.md`: Updated Critical Issues section to reflect resolved state. Updated last-updated date.
- `Claude.md`: Rewritten removing unicode characters, updated tech stack, removed stale reference to `docs/RefundFix.md`.
- `docs/specs/README.md`: Already up to date.
- All 14 markdown files verified to exist and have correct content.

---

## 10. Production Readiness Check

**Strengths:**
- Comprehensive exception handling in `bootstrap/app.php` (401/422/404/500 JSON envelopes)
- All financial models have `exchange_rate_snapshot` columns (migrations verified)
- Rate limiting configured for API routes
- HTTPS forcing available in production via `APP_FORCE_HTTPS`
- Console commands for backfill, validation, and verification
- Proper DB transaction usage in all write operations

**Recommendations:**
| Area | Status |
|------|--------|
| Test coverage | Thin — only 4 feature tests. Recommend expanding unit/feature tests |
| `app/Commands/` directory | Contains 6 artisan commands outside Laravel's auto-discovered `app/Console/Commands/`. Should be moved or formally registered |
| `config/app.version` | Referenced by `HealthCheckHandler` but not set in `config/app.php` |
| Missing `.env` guards | Production deploy script checks for `.env` existence, but there's no fail-safe at the app level |

---

## 11. Files Changed

```
 M .env.example                          # Cleaned duplicates, added missing env vars
 M Claude.md                             # Rewritten with correct versions
 M antigravity.md                        # Updated versions, marked issues resolved
 M app/Handlers/Admin/Operations/CreatePackageHandler.php
 M app/Handlers/Admin/Operations/ProcessBookingRefundHandler.php
 M app/Handlers/Admin/Operations/UpdatePackageHandler.php
 M app/Http/Actions/Web/Admin/Operations/CreatePackageAction.php
 M app/Http/Actions/Web/Admin/Operations/UpdatePackageAction.php
 M app/Http/Requests/Admin/Operations/AssignPackageRequest.php
 M app/Http/Requests/Admin/Operations/CreatePackageRequest.php
 M app/Http/Requests/Admin/Operations/UpdatePackageRequest.php
 M app/Models/AppNotification.php          # Added declare(strict_types=1)
 M app/Models/ClassCategory.php            # "
 M app/Models/ClassImage.php               # "
 M app/Models/ClassSession.php             # "
 M app/Models/Classes.php                  # "
 M app/Models/Instructor.php               # "
 M app/Models/Language.php                 # "
 M app/Models/MobileAppVersion.php         # "
 M app/Models/NotificationTemplate.php     # "
 M app/Models/PushNotificationLog.php      # "
 M app/Models/RecurrencePattern.php        # "
 M app/Models/StaticPage.php               # "
 M app/Models/Testimonial.php              # "
 M app/Models/UserSetting.php              # "
 M app/Services/BookingSession/BookingSessionService.php
 M routes/console/console.php              # Fixed schedule loading
?? README.md                               # Created
```
