# Pilates Studio SY

A Laravel 12 monolithic web application for managing a fitness center's subscriptions, clients, merchandise store, and financial operations. Features an admin panel with real-time operations, scheduler, multi-currency financial reporting, and a mobile API.

## Tech Stack

- **Backend:** PHP 8.4+, Laravel 12, MySQL
- **Admin Panel:** Filament v5
- **API:** Laravel Sanctum (mobile), Session-based (admin web)
- **Push Notifications:** Firebase Cloud Messaging (FCM)
- **Multi-language:** English/Arabic via Spatie Translatable
- **Multi-currency:** Option B architecture (Base Price + Exchange Rate)
- **Frontend:** Blade + Vanilla ES6 JS, Tailwind CSS v4, Vite

## Architecture

```
Action → Handler → Service → Repository → Model
```

- **Actions** — Single-invocation HTTP layer
- **Handlers** — Business logic (readonly services)
- **Services** — Domain services
- **Repositories** — Data persistence (Eloquent)
- **Value Objects** — Immutable data transfer objects

## Modules

- **Operations POS** — Client management, package assignment, merchandise store, financial daily balance, push notifications
- **Scheduler** — Daily class session management, attendance tracking, walk-in management
- **Reports (Filament)** — Per-currency financial summaries with optional base conversion
- **Mobile API (v1)** — Sanctum-protected endpoints for booking, class sessions, profiles, notifications
- **Landing Page** — Dynamic public site with class schedule, instructors, packages, testimonials
    
## Setup

```bash
cp .env.example .env
# Edit .env with your database and service credentials

composer install
npm install && npm run build

php artisan key:generate
php artisan migrate:fresh --seed

# Create storage symlink (required for images)
rm -f public/storage && ln -s ../storage/app/public public/storage
```

## Storage & Images

All uploaded images live in `storage/app/public/`. The `public/storage` symlink serves them to the web.

```bash
# Verify symlink exists and is correct
ls -la public/storage  # → ../storage/app/public

# Verify images are accessible
php artisan tinker --execute="echo \App\Models\ClassImage::first()->image_url;"
```

**Important:** Never use `php artisan storage:link` on production (Hostinger disables `exec()`). The deploy script creates it automatically. See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Deployment

Push to `dev` or `production` branch to trigger CI/CD deploy. See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for full guide.

```bash
# Normal deploy (safe, preserves admin data)
git push origin dev

# Re-seed deploy (DESTRUCTIVE — drops all tables)
git commit --allow-empty -m "chore: reseed [fresh-migrate]"
git push origin production
```

## Development

```bash
composer run dev
```

## Testing

```bash
composer run test
```

## Commands

| Command | Description |
|---------|-------------|
| `sessions:send-reminders` | Send 1-hour session reminders (every 5 min) |
| `sessions:remind-24h` | Send 24-hour session reminders (daily 08:00) |
| `finance:backfill-exchange-snapshots` | Backfill missing exchange rate snapshots |
| `finance:verify-daily-balance` | Verify daily balance totals |
| `config:validate-financial` | Validate financial configuration |
