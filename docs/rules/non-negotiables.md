# Non-negotiables

- `declare(strict_types=1)` in every PHP file; PHP 8.4+, Laravel 12, Filament v5.
- Run `./vendor/bin/pint` before committing (preset `laravel`, see `pint.json`).
- Translatable models use Spatie Translatable (English/Arabic).
- Money is stored as integers; display with the currency's `decimal_places` and code.
- Every financial row carries an exchange-rate snapshot (`exchange_rate_snapshot`).
- Refund amount never exceeds `paid_amount`.
- Any change to booking status (freeze, unfreeze, refund) validates the current
  state first and keeps the audit trail.
- Multi-step writes run inside a DB transaction.
- Actions catch `\Throwable`, log with context, return the standard JSON error.
- Never commit `public/storage` (gitignored symlink) or `.env` secrets.
- Images: see [images-and-storage.md](images-and-storage.md).
- Deploy and branches: see [deployment.md](deployment.md).
