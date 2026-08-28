# A14 — `class_sessions` never transition to `completed`

| | |
|---|---|
| **Verdict** | Would silently break the session picker |
| **Impact** | Medium |
| **Status** | Accepted |

## What the code does

`ClassSessionStatusEnum` defines `COMPLETED` (`app/Enums/ClassSessionStatusEnum.php:12`), and
`class_sessions.status` defaults to `SCHEDULED`
(`database/migrations/2024_01_01_000055_create_class_sessions_table.php:29-30`).

**Nothing ever writes `COMPLETED`.** No scheduled command, no observer, no service. The enum
case appears only in stats counting, the factory, and seeders.

## Consequence

Every past session in the database still has `status = scheduled`.

The backfill session-picker query must therefore filter:

```php
->whereDate('date', '<=', today())
->where('status', '!=', ClassSessionStatusEnum::CANCELLED->value)
```

Filtering `status = completed` returns **zero rows** — a silent empty picker with no error.
