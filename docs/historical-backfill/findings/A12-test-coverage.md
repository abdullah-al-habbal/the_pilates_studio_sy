# A12 — Bookings have near-zero test coverage

| | |
|---|---|
| **Verdict** | Context for Phase 7 sizing |
| **Impact** | Medium |
| **Status** | Accepted |

## What exists

```
tests/Feature/BookingCapacityTest.php        <- only booking-adjacent file
tests/Feature/ClassCategorySlugTest.php
tests/Feature/ClassRegenerationTest.php
tests/Feature/ClassWeekdayGenerationTest.php
tests/Feature/ClassesAdminPanelTest.php
tests/Feature/ClassesFormSchemaTest.php
tests/Feature/SessionConflictDetectorTest.php
tests/Unit/SessionDateCalculatorTest.php
```

Coverage is scheduling-focused. Package assignment, freeze/unfreeze, refunds, walk-ins and
merchandise have none.

## Infrastructure is ready

| Concern | Setting |
|---|---|
| Connection | MySQL — `phpunit.xml:26-27`, database `pilates_studio_test_db` |
| Why not sqlite | `users.is_active` uses `storedAs("IF(...)")`; sqlite cannot run the schema |
| Base class | `tests/TestCase.php` — bare, no traits |
| Refresh | `use RefreshDatabase` per test class |
| Test attribute | `PHPUnit\Framework\Attributes\Test` |
| Class style | `final class …Test extends TestCase` |
| Factories | exist for `Booking`, `BookingSession`, `ClassSession`, `Classes`, `Package`, `User`, `Instructor`, `ClassCategory` |

Running on MySQL matters here: `unique_active_booking_per_user` is a generated column, so
[A01](A01-active-booking-constraint.md) is genuinely testable.
