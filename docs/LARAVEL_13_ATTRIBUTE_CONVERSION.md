# Laravel 13 Attribute-First Conversion Runbook

The whole repository runs the Laravel 13 attribute-first style. A file is "done" when it declares **no legacy property form** for any concern an installed attribute covers. Source of truth: `vendor/laravel/framework/src/Illuminate/**/Attributes/*.php` and `.claude/skills/laravel-attributes/SKILL.md`.

Repo baseline: `laravel/framework ^13` (locked v13.29.0), PHP 8.5, `filament/filament ^5`.

Sources (both must be cross-checked against vendor): [laraveldaily.com — PHP Attributes in Laravel 13: The Ultimate Guide](https://laraveldaily.com) · [deploynix.io — Laravel 13 Native Attributes: Slimmer Models and a Cleaner Codebase](https://deploynix.io/blog/laravel-13-native-attributes-slimmer-models-and-a-cleaner-codebase-1).

> **Blog attributes NOT installed (do not use).** The deploynix blog walks through `#[Casts]`, `#[With]`, `#[ListensTo]`, `#[OnQueue]`, `#[Channels]`, `#[Envelope]`, `#[BroadcastOn]` / `#[BroadcastAs]`. None exist in `laravel/framework 13.29.0` — there are no `Events/Attributes`, `Mail/Attributes`, `Notifications/Attributes`, `Broadcasting/Attributes` directories in any released version. Keep the legacy equivalents: `casts()` method, `$with` property, `EventServiceProvider::$listen`, `#[Queue('name')]` (not `#[OnQueue]`), `via()`, `envelope()`, `broadcastOn()`/`broadcastAs()`. Only **nine** attribute namespaces ship: `Console`, `Container`, `Database/Eloquent`, `Database/Eloquent/Factories`, `Foundation/Http`, `Foundation/Testing`, `Http/Resources`, `Queue`, `Routing/Controllers`.

---

## Done matrix — what was converted and how to keep it that way

| Concern | Attribute (import) | Replacement | Converted in this repo |
|---|---|---|---|
| Mass assignment | `#[Fillable([...])]` `Illuminate\Database\Eloquent\Attributes\Fillable` | `protected $fillable` | all 27 models |
| Hidden serialization | `#[Hidden([...])]` | `protected $hidden` | `User` |
| Table / key / timestamps | `#[Table(name:..., key:..., keyType:..., incrementing:..., timestamps:...)]` | `$table`, `$primaryKey`, `$keyType`, `$incrementing`, `$timestamps`, `$dateFormat` | `ClassImage` |
| Local scope | `#[Scope] protected function foo(Builder $q, ...)` | `scopeFoo($query)` | 5 scopes (`User`×2, `Classes`, `Booking`, `CenterMerchandiseImage`) |
| Observer wiring | `#[ObservedBy([XObserver::class])]` | `EventServiceProvider::$observers` | 5 observers |
| Policy binding | `#[UsePolicy(XPolicy::class)]` | provider `$policies` array | `BookingSession` (provider array removed) |
| Factory model | `#[UseModel(X::class)]` `Illuminate\Database\Eloquent\Factories\Attributes\UseModel` | `protected $model` | 7 factories (6 converted, 1 created) |
| Resource collection | `#[Collects(XResource::class)]` `Illuminate\Http\Resources\Attributes\Collects` | `public $collects` | `BookingCollection`, `BookingSessionCollection` |
| Command signature | `#[Signature('...')]` `Illuminate\Console\Attributes\Signature` | `protected $signature` | 6 commands |
| Command description | `#[Description('...')]` | `protected $description` | 6 commands |
| Queued listener tuning | `#[Tries(3)]` (others in `Illuminate\Queue\Attributes\`) | `public $tries`, `$timeout`, `$backoff`, ... | `CreateDefaultUserSettingListener` |
| Translatable | `#[Translatable(['name'])]` `Spatie\Translatable\Attributes\Translatable` | `public array $translatable` | bilingual models |

Converted observers: `BookingObserver`, `ClassesObserver`, `ClassSessionObserver`, `MerchandiseOrderObserver`, `CenterMerchandiseCategoryObserver` — all `final readonly` with constructor DI.
Converted commands: `CleanupLogsCommand`, `VerifyDailyBalanceCommand`, `Send24HourSessionReminders`, `SendSessionRemindersCommand`, `ValidateFinancialConfig`, `BackfillExchangeRateSnapshotsCommand`.
Converted factories: `Currency`, `Refund`, `AppSetting`, `ClubExpense`, `CenterMerchandiseCategory`, `BookingSession`; missing `CenterMerchandiseFactory` **created** (its absence broke `MerchandiseOrder::factory()`).

---

## 1. Models

| Concern | Legacy | Attribute |
|---|---|---|
| Mass assignment | `protected $fillable = [...]` | `#[Fillable([...])]` |
| Hidden / Visible serialization | `$hidden` / `$visible` | `#[Hidden([...])]` / `#[Visible([...])]` |
| Table / key / timestamps / date format | `$table`, `$primaryKey`, `$keyType`, `$incrementing`, `$timestamps`, `$dateFormat` | `#[Table(name:..., key:..., keyType:..., incrementing:..., timestamps:...)]` / `#[DateFormat('...')]` |
| Connection | `$connection` | `#[Connection('name')]` |
| Touches | `$touches` | `#[Touches([...])]` |
| Appends | `$appends` | `#[Appends([...])]` |
| Local scope | `scopeFoo($query)` | `#[Scope] protected function foo(Builder $query, ...)` |
| Global scope | `booted() + addGlobalScope` | `#[ScopedBy(GlobalScope::class)]` |
| Observer wiring | `EventServiceProvider::$observers` | `#[ObservedBy([XObserver::class])]` |
| Policy | provider `$policies` | `#[UsePolicy(XPolicy::class)]` |
| Computed accessor | `getFooAttribute()` | `protected function foo(): Attribute` returning `Attribute::make(...)` |
| Serialize keys | `casts()->{'$model->toResource()'}` won't compile on 13 | see §5 |
| Custom collection | `newCollection()` | `#[CollectedBy(XCollection::class)]` |
| Custom builder | `newEloquentBuilder()` | `#[UseEloquentBuilder(XBuilder::class)]` |
| Route key | `getRouteKeyName()` | `#[RouteKey('slug')]` |
| Lifecycle hook | `booted()` | `#[Boot]` on method |
| Per-instance init | `initializeX()` | `#[Initialize]` on method |
| Non-incrementing / no timestamps | `$incrementing = false` / `$timestamps = false` | `#[WithoutIncrementing]` / `#[WithoutTimestamps]` |
| Factory | `newFactory()` | `#[UseFactory(XFactory::class)]` |

Rules:

- One attribute per line, directly above `class X extends Model`.
- `casts()` stays a **method** — no `#[Casts]` exists in any released Laravel.
- `#[Scope]` method name is the scope name; callers `Model::foo(...)` / `$query->foo(...)` unchanged.
- `getFooAttribute()` → `Attribute::make(get: ...)` keeps magic property `$model->foo`; callers unchanged.
- `#[Fillable]`/`#[Hidden]`/`#[ObservedBy]` are **13-only** — on Laravel 12 attributes silently don't exist: models become `guarded = ['*']` (mass assignment throws) and observers never run. Keep the repo on `^13`.
- Do not re-register policies or observers in providers once the attribute exists on the model.

Done when: `grep -rn "protected \$fillable\|protected \$hidden\|protected \$guarded\|protected \$appends\|protected \$visible\|protected \$with\|protected \$touches\|protected \$connection\|protected \$dateFormat\|function scope\|get[A-Z]\w*Attribute(\|getRouteKeyName\|protected \$casts" app/Models` returns nothing.

## 2. Observers

1. Class: `final`; use `readonly` when it holds constructor-injected services (all do).
2. Constructor DI — Laravel resolves observers from the container whether registered via `#[ObservedBy]` or provider map. Never `app(...)` inside a hook.
3. Attach on the model: `#[ObservedBy([XObserver::class])]`.
4. **Remove** the model+observer pair from `EventServiceProvider::$observers` (and module providers). Leaving it double-fires every hook.
5. `handlesAuthorization`/queue timing belongs on models/jobs, not observers.

## 3. Listeners

Event→listener mapping stays in `EventServiceProvider::$listen`. There is **no** attribute replacing `$listen` in any released Laravel. Converting a listener means queue behaviour only (`Illuminate\Queue\Attributes\*`): `#[Tries]`, `#[Timeout]`, `#[Backoff]`, `#[MaxExceptions]`, `#[Queue]`, `#[Connection]`, `#[Delay]`, `#[DebounceFor]`, `#[UniqueFor]`, `#[FailOnTimeout]`, `#[DeleteWhenMissingModels]`, `#[WithoutRelations]`.

Rules:

- Class attributes directly above `class`; combine freely.
- `InteractsWithQueue` only needed when calling `$this->release()/delete()/attempts()` — dropping it when unused is the default.
- Keep `failed()` for permanent-failure logging; plain method, stays.

Done in repo — `app/Listeners/User/CreateDefaultUserSettingListener.php`:

```php
#[Tries(3)]
class CreateDefaultUserSettingListener implements ShouldQueue
{
    public function __construct(
        private readonly LanguageEloquentRepository $languageRepo,
    ) {}

    public function handle(UserRegisteredEvent $event): void { ... }
    public function failed(UserRegisteredEvent $event, Throwable $exception): void { ... }
}
```

## 4. Jobs

Same `Illuminate\Queue\Attributes\*` as listeners. A queued job keeps `use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;` and `handle()`; only property-based queue config becomes attributes. `app/Jobs/Auth/SendOtpJob.php` already uses the trait block with no `$tries`/`$timeout` properties — nothing to convert; add attributes only when a job needs non-default values.

## 5. Notifications

`implements ShouldQueue` on the class stays (interface, not property). Queue tuning uses the queue attributes. `via()` stays a method — no `#[Channels]` in any release. Existing `SessionReminderNotification` / `ManualPushNotification` have no legacy properties.

## 6. Console commands

All app commands use `#[Signature]` / `#[Description]`. Also available: `#[Help]`, `#[Hidden]`, `#[Usage(...)]` (repeatable), `#[Aliases([...])]`. Multiline signatures must preserve whitespace byte-for-byte. After editing, `php artisan list` must show the new signature/description.

## 7. Factories

`protected $model = X::class` → `#[UseModel(X::class)]`. Model-side `#[UseFactory(XFactory::class)]` (Eloquent namespace) only when the factory name isn't derivable. After editing: `php artisan tinker --execute="App\Models\X::factory()->make()"` per factory.

## 8. Policies

`#[UsePolicy(BookingSessionPolicy::class)]` on `BookingSession`. Gate resolves it in `getPolicyFor()` when the model isn't in a provider array. The former `ApplicationServiceProvider::$policies` array + `registerPolicies()` loop + `Gate` import were **removed** — never re-register (duplicate is dead config; attribute resolves first).

Verify: `php artisan tinker --execute="app('Illuminate\Contracts\Auth\Access\Gate')->getPolicyFor(App\Models\BookingSession::class)"` → `App\Policies\BookingSessionPolicy`.

## 9. Resource collections

`public $collects = X::class` → `#[Collects(X::class)]` (read by `CollectsResources`). Converted: `BookingCollection`, `BookingSessionCollection`.

Not converted and why:
- `#[UseResource]` / `#[UseResourceCollection]` (model `->toResource()`) — controllers call `XResource::make($model)` / `XResource::collection(...)` explicitly, so the attributes are never read. Adopt only in the same PR that migrates controllers to `toResource()`.
- `#[PreserveKeys]` — no collection preserves keys.

## 10. Container / DI

`Illuminate\Container\Attributes\*` parameter attributes (`#[CurrentUser]`, `#[Config]`, `#[DB]`, `#[Log]`, `#[Storage]`, `#[Cache]`, `#[Auth]`, `#[Tag]`, `#[Context]`, `#[RouteParameter]`, `#[RequestAttribute]`, `#[Give]`) and class-level registration attrs (`#[Bind]`, `#[Singleton]`, `#[Scoped]`, `#[BindWhen]`). Not used in this repo — services/actions use plain constructor DI and the provider `bind()`s are already written. Acceptable use cases: injecting config values / tagged services / current user into a constructor param without pulling `Auth::user()` manually.

## 11. Nothing to convert — keep as methods/properties (no attribute exists)

| Item | Reason |
|---|---|
| `casts()` (24 models) | no `#[Casts]` in any release |
| `$with` eager-load defaults | no `#[With]` in any release |
| `EventServiceProvider::$listen` | no `#[ListensTo]` / `#[Listen]` in any release |
| `shouldDiscoverEvents()` | no attribute replaces it |
| `via()` (2 notifications) | no `#[Channels]` in any release |
| `envelope()` | no `#[Envelope]` (repo has no mailables) |
| `broadcastOn()` / `broadcastAs()` | no broadcast attrs (repo has no broadcast events) |
| `$translatable` on models WITHOUT `HasTranslations` | must stay property; only `HasTranslations` models get `#[Translatable]` (12/12 bilingual models converted) |
| `use RefreshDatabase;` in tests | no RefreshDatabase attribute |
| `setUp()` / `tearDown()` in TestCase | convertible to `#[SetUp]`/`#[TearDown]` only for self-registering hooks; repo has none |
| `$this->middleware(...)` | none exist; don't invent them to use `#[Middleware]` |

---

## Pitfalls

1. **Double-firing observers.** In `#[ObservedBy]` AND `EventServiceProvider::$observers` → every hook runs twice. Exactly one registration.
2. **Laravel 12 vs 13.** Model/observer attributes are 13-only. On 12 they silently no-op → guarded models, dead observers. Repo must stay `^13`.
3. **Scope rename.** `scopeBlockingNewPurchase()` → `#[Scope] protected function blockingNewPurchase()`. Public calls unchanged; do not "fix" callers.
4. **Accessor rename.** `getFooAttribute()` → `foo(): Attribute`. Magic property unchanged; do not touch callers.
5. **readonly observers.** Resolved by container; fine. Only drop `readonly` if a hook mutates observer state (don't).
6. **Observer DI.** Works through the container on both registration paths — constructor injection covers `#[CurrentUser]`, `#[Config]`, etc.
7. **Policy double-registration.** `#[UsePolicy]` + provider `$policies` → dead duplicate. Attribute resolves first; provider copy is stale config.
8. **`$collects` forgotten.** Removing the property without adding `#[Collects]` silently breaks collection mapping — the collection renders raw models. Always grep-sweep after edits.
9. **Blog fiction.** `#[Casts]`, `#[ListensTo]`, `#[OnQueue]`, `#[Channels]`, `#[Envelope]`, `#[BroadcastOn]`, `#[BroadcastAs]`, `#[With]` do not exist. Verify every claim against `ls vendor/laravel/framework/src/Illuminate/*/Attributes/`.
10. **Phantom attributes.** Any attribute outside the nine canonical namespaces will not compile — treat as nonexistent.

---

## Verification

```bash
# 1. No legacy property/accessor/scope/command/factory/collection config anywhere
grep -rn 'protected \$fillable\|protected \$hidden\|protected \$guarded\|protected \$appends\|protected \$visible\|protected \$with\|protected \$touches\|protected \$connection\|protected \$dateFormat\|function scope\|get[A-Z]\w*Attribute(\|protected \$signature\|protected \$description\|protected \$model\|public \$collects\|protected \$casts' app database/factories

# 2. Every observer attached exactly once (attribute present, provider clean)
grep -rn 'ObservedBy' app/Models
grep -n 'observers' app/Providers/EventServiceProvider.php    # expect: no match

# 3. Policy resolves through the attribute
php artisan tinker --execute="app('Illuminate\Contracts\Auth\Access\Gate')->getPolicyFor(App\Models\BookingSession::class)"

# 4. Factories resolve their model
php artisan tinker --execute="App\Models\X::factory()->make()"

# 5. Commands discoverable + app listeners intact
php artisan list
php artisan event:list

# 6. Format + tests
vendor/bin/pint
php artisan test

# 7. Keep the knowledge graph in sync
graphify update .
```

Note: `phpstan`/`larastan` not installed (`vendor/bin/phpstan` absent). Add `larastan/larastan` in `require-dev` if static analysis is wanted.