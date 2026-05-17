# Project: Gym Management & POS System

## Overview
This is a **Laravel 10+ monolithic web application** for managing a fitness center’s subscriptions, clients, merchandise store, and financial operations. It includes an admin panel with real‑time operations (freeze/unfreeze bookings, assign packages, handle walk‑in orders, process refunds, record expenses).

## Tech Stack
- **Backend:** PHP 8.1+, Laravel, MySQL/PostgreSQL.
- **Frontend:** Vanilla JavaScript (with some Blade‑rendered partials), Tailwind CSS.
- **Key packages:** Laravel’s Eloquent, FormRequest validation, API‑style responses, DB transactions.

## Architecture Highlights
- **Actions** (single‑invocation controllers) handle HTTP requests. They call **Handlers** that contain business logic.
- **Validators** (like `RefundValidatorService`) enforce domain rules before any state change.
- **Routes** are defined in `routes/web/operations.php` under the `admin/operations` prefix, protected by `auth` and `freeze.user` middlewares.
- **API utilities** are encapsulated in `public/js/operations/api.js` (the `OperationsAPI` object). All admin AJAX calls go through this module.

## Development Conventions
- **Strict types** (`declare(strict_types=1)`) everywhere.
- **Readonly classes** are used for services/handlers that don’t hold mutable state.
- **FormRequest validation** is preferred; for complex business rules an explicit `assert*` method is used.
- **Database transactions** wrap any multi‑step write operations (e.g., refund + status update).
- **Error handling:** Catch `\Throwable` in actions, log with context, return a standard JSON error response.
- **Frontend interactions:** Modals are managed by `OperationsUI.openModal()`, toast notifications by `OperationsUI.toast()`. All refund/freeze/unfreeze actions go through the shared `OperationsAPI.refundBooking()` etc.

## Key Models
- `Booking`: Represents a customer’s subscription package. Fields: `status`, `paid_amount`, `expires_at`, `exchange_rate_snapshot`, `remaining_credits`.
- `Refund`: Polymorphic model for refunds; stores amount, currency snapshot, and refunded_by.
- `User`/client: the customer, linked to bookings and store orders.

## Sensitive Areas
- Any operation that alters booking status (freeze, unfreeze, refund) must validate the current state and preserve audit trails.
- Currency amounts are stored in the smallest unit (integer) and must be displayed using the correct `decimal_places` and currency code.
- Refund amounts must never exceed `paid_amount`.

## Testing & Debugging
- Check `/storage/logs/laravel.log` for full exception traces.
- Use browser **Network** tab to inspect JSON payloads and responses.
- Run `php artisan tinker` to quickly inspect booking and refund records.

## Recent Changes (2026-05-17)
- Fixed a bug where leaving the refund amount field empty caused a validation error (details in `docs/RefundFix.md`).
