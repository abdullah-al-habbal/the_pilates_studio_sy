# A03 — Credits decrement at reserve time, not attendance time

| | |
|---|---|
| **Verdict** | Confirms the PRD's arithmetic |
| **Impact** | None — no work required |
| **Status** | Accepted |

## What the code does

`BookingSessionService::reserve()` decrements the credit when the reservation is created:

```php
// app/Services/BookingSession/BookingSessionService.php:212
$this->bookingService->decrementCredits($booking);
```

Attendance marking never touches credits:

- `BookingSessionEloquentRepository::markAttended()` `:169` — sets `attendance_status`, `attended_at`
- `BookingSessionEloquentRepository::markMissed()` `:178` — sets `attendance_status`, clears `attended_at`

## Consequence

Every `booking_sessions` row — attended **or** missed — has already consumed one credit.

So the PRD's reconciliation rule

```
remaining_credits = total_credits − attended_count − missed_count
```

matches production semantics exactly. No adaptation needed; the backfill writes the same
shape the live flow produces.
