# Pilates Studio SY

Laravel 12 monolith for a pilates studio: subscriptions, clients, merchandise
store, scheduler, multi-currency finance, and a mobile API.

**Stack:** PHP 8.4+, Laravel 12, MySQL, Filament v5, Sanctum, Firebase FCM,
Spatie Translatable (en/ar), Blade + vanilla JS, Tailwind v4, Vite.

**Architecture:** `Action -> Handler -> Service -> Repository -> Model`.

**Modules:** Operations POS, Scheduler, Filament reports, Mobile API v1, landing site.

## Quick start

```bash
cp .env.example .env            # set DB and service credentials
composer install && npm install && npm run build
php artisan key:generate
php artisan migrate:fresh --seed
rm -f public/storage && ln -s ../storage/app/public public/storage
composer run dev
composer run test
```

Never use `php artisan storage:link` on Hostinger (`exec()` is disabled).

## Deploy

Push to `dev` or `production` to deploy. `[fresh-migrate]` in the commit
message wipes and reseeds, so use it deliberately.

## Docs

Start at [docs/index.md](docs/index.md); agents also read [AGENTS.md](AGENTS.md)
and [CLAUDE.md](CLAUDE.md).
