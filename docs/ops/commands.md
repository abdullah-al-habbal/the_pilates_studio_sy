# Commands

| Task | Command |
|------|---------|
| First-time setup | `composer run setup` (or `cp .env.example .env`, `composer install`, `npm install && npm run build`, `php artisan key:generate`, `php artisan migrate:fresh --seed`) |
| Dev stack (server, queue, pail, vite) | `composer run dev` (`dev:win` on Windows) |
| Local reset + reseed + dev | `composer run restart-local` |
| Assets | `npm run dev` / `npm run build` |
| Format | `./vendor/bin/pint` |
| Tests | see [../rules/testing.md](../rules/testing.md) |

Artisan commands:

| Command | Purpose | Schedule |
|---------|---------|----------|
| `sessions:send-reminders` | 1-hour session reminders | every 5 min |
| `sessions:remind-24h` | 24-hour reminders | daily 08:00 |
| `finance:backfill-exchange-snapshots` | backfill missing snapshots | manual |
| `finance:verify-daily-balance` | verify daily totals | manual |
| `config:validate-financial` | validate financial config | manual |
| `logs:cleanup` | prune logs (`CleanupLogsCommand`) | see known-gaps |
