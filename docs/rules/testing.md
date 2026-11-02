# Testing

- `composer run test` (clears config, then `php artisan test`).
- Single test: `php artisan test --filter=BookingCapacityTest` or
  `php artisan test tests/Feature/BookingCapacityTest.php`.
- Suites: `Unit`, `Feature` (`phpunit.xml`). Tests hit a real MySQL database
  `pilates_studio_test_db` on 127.0.0.1 (not SQLite); create it before running.
- Logs: `storage/logs/laravel.log`; quick data inspection with `php artisan tinker`.
