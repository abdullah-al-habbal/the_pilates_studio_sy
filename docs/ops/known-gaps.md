# Known gaps

- Two console route files exist: only `routes/console/console.php` is loaded
  (`bootstrap/app.php`). `routes/console.php` is a stale copy that still holds
  the `logs:cleanup` daily 03:00 schedule, so that job is not scheduled.
- `phpunit.xml` hard-codes MySQL root credentials for the test database.
- Overlapping DTO folders: `app/DTOs`, `app/Dtos`, `app/Data`.
- Production API 404 incident (2026-09-12): root cause still unknown; see
  commit `243f738` for the report.
