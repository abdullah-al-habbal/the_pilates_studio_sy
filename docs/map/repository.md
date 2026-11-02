# Repository map

| Area | Where |
|------|-------|
| Bootstrap, middleware aliases, API 404/exception handling | `bootstrap/app.php` |
| Web routes (entry `routes/web/web.php`) | `routes/web/{operations,scheduler}/*.php`, `locale.php`, `static-pages.php` |
| API routes (entry `routes/api/api.php`) | `routes/api/v1/{public,protected,admin}` |
| Operations POS actions (`/admin/operations`, `auth` + `freeze.user`) | `app/Http/Actions/Web/Admin/Operations/` |
| Mobile API actions | `app/Actions/V1/`, `app/Http/Controllers/Api` |
| Business logic | `app/Handlers/{Admin,Booking,Finance,User,V1}` |
| Domain services / repositories | `app/Services/<Entity>`, `app/Repositories/Eloquent` |
| Admin panel | `app/Filament/Admin/{Resources,Pages,Widgets}`, `app/Providers/Filament` |
| Scheduler UI JS | `public/js/scheduler/` (ES modules: api, state, render, modal, walkin) |
| Operations UI JS | `public/js/operations/` (`api.js` = `OperationsAPI`, `main.js`, `modules/`) |
| Landing JS | `public/js/web/landing/` |
| Views | `resources/views/{admin,landing,layouts,components,emails,filament}` |
| Translations | `lang/{en,ar}` |
| Artisan commands | `app/Console/Commands`, schedule in `routes/console/console.php` |
| Push notifications (FCM) | `app/Providers/FcmServiceProvider.php`, `app/Notifications`, `SendPushNotificationCommand` |
| API docs (Scramble) | `app/Providers/ScrambleServiceProvider.php` |
| Tests | `tests/Feature`, `tests/Unit` |

Frontend conventions: all admin AJAX goes through `OperationsAPI` (e.g.
`OperationsAPI.refundBooking()`); modals via `OperationsUI.openModal()`, toasts
via `OperationsUI.toast()`. Tailwind v4 + Vite build `resources/css|js` only.

Domain: [domain.md](domain.md). Commands: [../ops/commands.md](../ops/commands.md).
