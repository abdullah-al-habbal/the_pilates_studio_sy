# Domain notes

- `Booking`: a client's package subscription. Fields `status`, `paid_amount`,
  `expires_at`, `exchange_rate_snapshot`, `remaining_credits`. Only one active
  booking per client (`app/Support/Booking/ActiveBookingConstraint.php`).
- `Refund`: polymorphic; stores amount, currency snapshot, `refunded_by`.
- `User`: the client; linked to bookings and store orders.
- Multi-currency (Option B): base price plus exchange rate; snapshots on every
  financial transaction; reports are per currency with optional base conversion.
- Modules: Operations POS (clients, packages, store, daily balance, approvals,
  notifications), Scheduler (daily sessions, attendance, walk-ins), Filament
  reports, Mobile API v1 (Sanctum), public landing site.
- Auth: Sanctum for mobile, session for admin web (guests go to `/admin/login`).
