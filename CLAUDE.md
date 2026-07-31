# CLAUDE.md — AI Agent Instructions

## Project Context

Laravel 12 + Filament v5 monolith for pilates studio management. PHP 8.4+, strict types.

## Key Conventions

### Image URLs — CRITICAL
All image URLs MUST use `Storage::disk('public')->url()`:
```php
// ✅ CORRECT
Storage::disk('public')->url($this->image)

// ❌ WRONG — breaks on production
url($this->image)
asset($this->image)
public_path($this->image)
```

Files live in `storage/app/public/`, served via `public/storage → ../storage/app/public` symlink.

**NEVER** use `php artisan storage:link` — Hostinger disables `exec()`.

### Coding Standards
- Run `./vendor/bin/pint` before committing
- PHP 8.4+ strict types
- Laravel 12 patterns (Actions → Handlers → Services → Repositories)
- Filament v5 for admin panel
- Translatable models via Spatie

### Deployment
- Push to `dev` → dev server
- Push to `production` → production server
- `[fresh-migrate]` in commit message → destructive reseed
- Normal pushes are always safe

## File Locations

| What | Where |
|------|-------|
| Image models | `app/Models/ClassImage.php`, `Instructor.php`, `StaticPage.php`, `Testimonial.php` |
| Image resources | `app/Http/Resources/Api/V1/ClassImageResource.php` |
| Filament image CRUD | `app/Filament/Admin/Resources/Classes/RelationManagers/ImagesRelationManager.php` |
| Storage config | `config/filesystems.php` (public disk) |
| Deploy script | `.github/workflows/deploy.yml` |
| Deployment docs | `docs/DEPLOYMENT.md` |

## Common Tasks

### Adding a new image field to a model
1. Add `image` column to migration
2. Add accessor: `return Storage::disk('public')->url($this->image)`
3. In Filament: `ImageEntry::make('image')` (defaults to public disk)
4. In API resource: `'image_url' => $this->image_url`

### Debugging 404 images
```bash
# Check symlink
ls -la public/storage

# Recreate if broken
rm -f public/storage && ln -s ../storage/app/public public/storage

# Check file exists
ls storage/app/public/{path-to-image}

# Check URL
curl -sI https://domain.com/storage/{path-to-image}
```

# Project: Pilates Studio SY - Gym Management & POS System

## Overview
This is a **Laravel 12 monolithic web application** for managing a fitness center's subscriptions, clients, merchandise store, and financial operations. It includes an admin panel with real-time operations (freeze/unfreeze bookings, assign packages, handle walk-in orders, process refunds, record expenses).

## Tech Stack
- **Backend:** PHP 8.4+, Laravel 12, MySQL.
- **Frontend:** Vanilla JavaScript (with some Blade-rendered partials), Tailwind CSS v4, Filament v5.
- **Key packages:** Laravel's Eloquent, FormRequest validation, Sanctum API auth, Firebase FCM, Spatie Translatable, Scramble API docs.

## Architecture Highlights
- **Actions** (single-invocation controllers) handle HTTP requests. They call **Handlers** that contain business logic.
- **Validators** (like `RefundValidatorService`) enforce domain rules before any state change.
- **Routes** are defined in `routes/web/operations.php` under the `admin/operations` prefix, protected by `auth` and `freeze.user` middlewares.
- **API utilities** are encapsulated in `public/js/operations/api.js` (the `OperationsAPI` object). All admin AJAX calls go through this module.

## Development Conventions
- **Strict types** (`declare(strict_types=1)`) everywhere.
- **Readonly classes** are used for services/handlers that don't hold mutable state.
- **FormRequest validation** is preferred; for complex business rules an explicit `assert*` method is used.
- **Database transactions** wrap any multi-step write operations (e.g., refund + status update).
- **Error handling:** Catch `\Throwable` in actions, log with context, return a standard JSON error response.
- **Frontend interactions:** Modals are managed by `OperationsUI.openModal()`, toast notifications by `OperationsUI.toast()`. All refund/freeze/unfreeze actions go through the shared `OperationsAPI.refundBooking()` etc.

## Key Models
- `Booking`: Represents a customer's subscription package. Fields: `status`, `paid_amount`, `expires_at`, `exchange_rate_snapshot`, `remaining_credits`.
- `Refund`: Polymorphic model for refunds; stores amount, currency snapshot, and refunded_by.
- `User`/client: the customer, linked to bookings and store orders.

## Sensitive Areas
- Any operation that alters booking status (freeze, unfreeze, refund) must validate the current state and preserve audit trails.
- Currency amounts are stored as integers and must be displayed using the correct `decimal_places` and currency code.
- Refund amounts must never exceed `paid_amount`.

## Testing & Debugging
- Check `/storage/logs/laravel.log` for full exception traces.
- Use browser **Network** tab to inspect JSON payloads and responses.
- Run `php artisan tinker` to quickly inspect booking and refund records.

## Recent Changes (2026-05-27)
- Multi-currency Option B architecture fully implemented (base price + exchange rate)
- Exchange rate snapshots on all financial transactions
- Per-currency financial reporting with optional base conversion
- Push notification system via Firebase Cloud Messaging
- Admin reports with Filament v5
