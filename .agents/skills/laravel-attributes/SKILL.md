---
name: laravel-attributes
description: MANDATORY reference for all new Laravel 13 PHP code in this repo. Use class attributes (#[Fillable], #[Table], #[Signature], #[ObservedBy], #[UsePolicy], #[Collects], #[UseModel], etc.) instead of class properties for Eloquent models, observers, policies, collections, jobs, listeners, console commands, factories, form requests, resources, testing hooks, and container injection. READ BEFORE WRITING any new PHP code. Sources: laraveldaily.com "PHP Attributes in Laravel 13: The Ultimate Guide (36 New Attributes)" and deploynix.io "Laravel 13 Native Attributes" — both must be cross-checked against installed vendor (see Canonical inventory).
---

# Laravel 13 PHP Attributes — Codified Reference

Attributes are **optional** and NOT breaking changes — old property syntax still works and ships forward. This repo's convention: **attributes only**. All existing property-style config has been migrated; new code MUST use attributes, never class properties, for anything covered by an installed attribute.

## Authority

The ONLY authority for whether an attribute exists is the installed framework:

```bash
ls vendor/laravel/framework/src/Illuminate/*/Attributes/
```

Any attribute claimed by a blog, tutorial, or article that is NOT in that tree does not exist in this repo's `laravel/framework` and MUST NOT be used. Existing code, tests passing, and `pint` runs prove nothing about an attribute's existence — the vendor tree is the gate.

## Canonical inventory (laravel/framework v13.29.0)

Nine attribute namespaces are installed. If a source names anything else (e.g. `Illuminate\Events\Attributes\*`, `Illuminate\Notifications\Attributes\*`, `Illuminate\Mail\Attributes\*`, `Illuminate\Broadcasting\Attributes\*`), it **does not exist** — the directories do not ship in any released version.

| Namespace | Attribute files (complete) | Repo status |
|---|---|---|
| `Console\Attributes` | Aliases, Description, Help, Hidden, Signature, Usage | ✅ applied (Signature/Description on all 6 commands; others N/A) |
| `Container\Attributes` | Auth, Authenticated, Bind, BindWhen, Cache, Config, Context, CurrentUser, DB, Database, Give, Log, RequestAttribute, RouteParameter, Scoped, Singleton, Storage, Tag | ❌ not needed (no primitive-param DI sites) |
| `Database\Eloquent\Attributes` | Appends, Boot, CollectedBy, Connection, DateFormat, Fillable, Guarded, Hidden, Initialize, ObservedBy, RouteKey, Scope, ScopedBy, Table, Touches, Unguarded, UseEloquentBuilder, UseFactory, UsePolicy, UseResource, UseResourceCollection, Visible, WithoutIncrementing, WithoutTimestamps | ✅ applied: Fillable, Hidden, Table, Scope, ObservedBy, UsePolicy; ❌ the rest N/A |
| `Database\Eloquent\Factories\Attributes` | UseModel | ✅ applied (6 factories) + CenterMerchandiseFactory created |
| `Foundation\Http\Attributes` | ErrorBag, FailOnUnknownFields, RedirectTo, RedirectToRoute, StopOnFirstFailure | ❌ no FormRequests in repo |
| `Foundation\Testing\Attributes` | Seed, Seeder, SetUp, TearDown, UnitTest | ❌ real but unused (tests use `use RefreshDatabase` trait + `setUp()`) |
| `Http\Resources\Attributes` | Collects, PreserveKeys | ✅ applied (Collects on 2 collections) |
| `Queue\Attributes` | Backoff, Connection, DebounceFor, Delay, DeleteWhenMissingModels, FailOnTimeout, MaxExceptions, Queue, ReadsQueueAttributes (support class, not an attribute), Timeout, Tries, UniqueFor, WithoutRelations | ✅ applied (Tries on listener); ❌ rest N/A |
| `Routing\Attributes\Controllers` | Authorize, Middleware, WithoutMiddleware | ❌ N/A (no `$this->middleware()`; middleware is route-level) |

Spatie extra: `Spatie\Translatable\Attributes\Translatable` (checked at `vendor/spatie/laravel-translatable/src/Attributes/`) — ✅ applied to bilingual models.

Repo baseline: `laravel/framework ^13` (v13.29.0 locked), PHP 8.5, `filament/filament ^5`.

## Sources & blog cross-reference

Primary sources (cross-check both against the Canonical inventory above):
- [laraveldaily.com — PHP Attributes in Laravel 13: The Ultimate Guide (36 New Attributes)](https://laraveldaily.com) — the fully-real attribute catalog. Mostly accurate; verify each name in vendor.
- [deploynix.io — Laravel 13 Native Attributes: Slimmer Models and a Cleaner Codebase](https://deploynix.io/blog/laravel-13-native-attributes-slimmer-models-and-a-cleaner-codebase-1) (Sameh Elhawary, Aug 17 2026) — migration essay, partly **fiction**. Its model/command/queue examples match reality; its listener/mailable/notification/broadcast examples do NOT exist in any released Laravel.

The deploynix blog claims these — they do NOT exist in 13.29.0 and MUST NOT be used:

| Blog claims | Claimed namespace | Installed in 13.29? | Use instead |
|---|---|---|---|
| `#[Casts]` | `Illuminate\Database\Eloquent\Attributes` | ❌ absent | `casts()` method (24 models) |
| `#[With]` | `Illuminate\Database\Eloquent\Attributes` | ❌ absent | `$with` property (keep property) |
| `#[ListensTo]` | `Illuminate\Events\Attributes` | ❌ absent (dir does not exist) | `EventServiceProvider::$listen` |
| `#[OnQueue]` | `Illuminate\Queue\Attributes` | ❌ absent | `#[Queue('name')]` |
| `#[Channels]` | `Illuminate\Notifications\Attributes` | ❌ absent (dir does not exist) | `via()` method |
| `#[Envelope]` | `Illuminate\Mail\Attributes` | ❌ absent (dir does not exist) | `envelope()` method (no mailables in repo) |
| `#[BroadcastOn]` / `#[BroadcastAs]` | `Illuminate\Broadcasting\Attributes` | ❌ absent (dir does not exist) | `broadcastOn()` / `broadcastAs()` |

"Do not fix" rule: `casts()`, `$listen`, `via()`, `envelope()`, `broadcastOn()`, `broadcastAs()` stay as methods/properties. There is no attribute replacement in any released Laravel, and there never will be a `#[Listen]` for the event→listener mapping.

**Related:** this skill answers *how a Laravel class is configured*. Before writing the code
inside it — queries, caching, queue dispatching, validation, HTTP integrations, authorization,
file handling, persistence — read
[../laravel-native-abstractions/SKILL.md](../laravel-native-abstractions/SKILL.md), which
answers *what Laravel already knows how to do for you*. Container and queue attribute signatures
live here; the decision of whether to reach for a framework API at all lives there.

---

## 1. Eloquent model attributes

Import from `Illuminate\Database\Eloquent\Attributes\`. Complete installed set with what each replaces and repo status:

| Attribute | Replaces | Repo status |
|---|---|---|
| `#[Fillable([...])]` | `protected $fillable` | ✅ all 27 models |
| `#[Guarded([...])]` | `protected $guarded` | ❌ never used |
| `#[Unguarded]` (marker) | `protected $guarded = []` | ❌ never used |
| `#[Hidden([...])]` | `protected $hidden` | ✅ User |
| `#[Visible([...])]` | `protected $visible` | ❌ never used |
| `#[Appends([...])]` | `protected $appends` | ❌ never used |
| `#[Table(...)]` | `$table`, `$primaryKey`, `$keyType`, `$incrementing`, `$timestamps`, `$dateFormat` | ✅ ClassImage |
| `#[Connection('name')]` | `protected $connection` | ❌ single-DB |
| `#[DateFormat('d.m.Y')]` | `protected $dateFormat` | ❌ never used |
| `#[Touches([...])]` | `protected $touches` | ❌ never used |
| `#[Scope]` on method | `scopeFoo($query)` | ✅ 5 scopes (`User`×2, `Classes`, `Booking`, `CenterMerchandiseImage`) |
| `#[ScopedBy(GlobalScope::class)]` | `booted() + addGlobalScope` | ❌ none |
| `#[ObservedBy([XObserver::class])]` | `EventServiceProvider::$observers` | ✅ 5 observers |
| `#[UseFactory(XFactory::class)]` (model side) | `newFactory()` | ❌ factories resolve by name |
| `#[UsePolicy(XPolicy::class)]` | provider `$policies` array | ✅ BookingSession |
| `#[UseResource(XResource::class)]` | drives `$model->toResource()` | ❌ controllers use `Resource::make()` — attribute never read |
| `#[UseResourceCollection(XCollection::class)]` | drives `$model->toResourceCollection()` | ❌ same reason |
| `#[CollectedBy(XCollection::class)]` | `newCollection()` | ❌ no custom collections |
| `#[UseEloquentBuilder(XBuilder::class)]` | `newEloquentBuilder()` | ❌ no custom builders |
| `#[RouteKey('slug')]` | `getRouteKeyName()` | ❌ default `id` |
| `#[Boot]` on method | `booted()` | ❌ no model lifecycle hooks |
| `#[Initialize]` on method | `initializeX()` | ❌ none |
| `#[WithoutIncrementing]` (marker) | `$incrementing = false` | ❌ never used |
| `#[WithoutTimestamps]` (marker) | `$timestamps = false` | ❌ never used |

```php
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

#[Fillable(['title', 'body', 'status'])]
#[ObservedBy([PostObserver::class])]
#[UsePolicy(PostPolicy::class)]
class Post extends Model
{
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }
}
```

`#[Table]` with all options (named args):

```php
#[Table(
    name: 'external_orders',
    key: 'uuid',
    keyType: 'string',
    incrementing: false,
    timestamps: false,
)]
class ExternalOrder extends Model {}
```

Rules:
- One attribute per line, directly above the class declaration, imports alphabetical (`pint` fixes order).
- `casts()` stays a **method**. There is no `#[Casts]` in any released Laravel. The `protected $casts` **property** still works in 13 (back-compat) but is legacy — repo convention is the method form; convert any `$casts` property found.
- `#[Scope]` method name is the scope name; public call `->foo(...)` unchanged.
- `getFooAttribute()` → `protected function foo(): Attribute` returning `Attribute::make(...)`; magic property `$model->foo` unchanged.
- `#[Boot]`/`#[Initialize]` method must be `protected` or `public`; attributes on methods go ABOVE the method.
- On Laravel 12 the attribute classes do not exist — models silently become guarded and observers never run. Whole repo must stay on `^13`.

## 2. Queue / job attributes

Import from `Illuminate\Queue\Attributes\`. Combine freely. Apply to jobs AND queued listeners/notifications.

| Attribute | Replaces | Repo status |
|---|---|---|
| `#[Tries(3)]` | `public $tries` | ✅ CreateDefaultUserSettingListener |
| `#[Timeout(120)]` | `public $timeout` | ❌ defaults |
| `#[Backoff(30)]` or `#[Backoff([10, 30, 60])]` | `public $backoff` | ❌ defaults |
| `#[MaxExceptions(3)]` | `public $maxExceptions` | ❌ defaults |
| `#[Queue('high')]` | `public $queue` | ❌ defaults (blog's `#[OnQueue]` does NOT exist) |
| `#[Connection('redis')]` | `public $connection` | ❌ defaults |
| `#[Delay(60)]` / `#[Delay(now()->addHour())]` | `public $delay` | ❌ defaults |
| `#[DebounceFor(10)]` | `public $debounce` | ❌ defaults |
| `#[UniqueFor(3600)]` | `public $uniqueFor` | ❌ never used |
| `#[FailOnTimeout]` (marker) | `$failOnTimeout = true` | ❌ never used |
| `#[DeleteWhenMissingModels]` (marker) | `$deleteWhenMissingModels = true` | ❌ never used |
| `#[WithoutRelations]` (marker) | `$withoutRelations = true` | ❌ never used |

```php
use Illuminate\Queue\Attributes\{Backoff, Connection, Queue, Timeout, Tries};

#[Connection('redis')]
#[Queue('high')]
#[Tries(3)]
#[Timeout(60)]
#[Backoff([5, 15, 30])]
class ProcessPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
}
```

A queued job/listener keeps its trait block (`InteractsWithQueue` only when it calls `release()`/`delete()`/`attempts()` etc.) and `handle()`; only property-based config moves to attributes.

## 3. Console command attributes

Import from `Illuminate\Console\Attributes\`. Complete set:

| Attribute | Replaces | Repo status |
|---|---|---|
| `#[Signature('users:prune {--days=30 : Days of inactivity}')]` | `protected $signature` | ✅ all 6 commands |
| `#[Signature('cache:warm', aliases: ['warm-cache'])]` | `$signature` + `$aliases` (use `Aliases` attr for extras) | ❌ no aliases |
| `#[Description('...')]` | `protected $description` | ✅ all 6 commands |
| `#[Help('...')]` | `protected $help` | ❌ none |
| `#[Hidden]` (marker) | `$hidden = true` | ❌ none |
| `#[Usage('users:prune --days=60')]` (repeatable) | help-text usage lines | ❌ none |

```php
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('users:prune {--days=30 : Days of inactivity}')]
#[Description('Prune inactive user accounts')]
class PruneUsers extends Command
{
    public function handle(): void {}
}
```

Multiline signatures: preserve newlines/indentation of `{--...}` continuation lines byte-for-byte inside the attribute string. Verify discovery with `php artisan list`.

Converted in this repo: `CleanupLogsCommand`, `VerifyDailyBalanceCommand`, `Send24HourSessionReminders`, `SendSessionRemindersCommand`, `ValidateFinancialConfig`, `BackfillExchangeRateSnapshotsCommand`.

## 4. Routing / controller attributes

Import from `Illuminate\Routing\Attributes\Controllers`: `Middleware`, `Authorize`, `WithoutMiddleware`. These replace constructor middleware/authorization on controllers.

N/A in this repo: API controllers rely on route-level middleware (`routes/*.php` `->middleware('auth:sanctum')`); no `$this->middleware(...)` calls exist, so there is nothing for the attribute to replace. Do not bolt `#[Middleware]` onto controllers that currently have none.

## 5. Form request attributes

Import from `Illuminate\Foundation\Http\Attributes`: `ErrorBag`, `FailOnUnknownFields`, `RedirectTo`, `RedirectToRoute`, `StopOnFirstFailure`. Replaces `$errorBag`, `$redirect`, `$redirectRoute`, `$stopOnFirstFailure`. N/A in this repo — no FormRequest classes (validation happens in Actions).

## 6. HTTP resource attributes

Import from `Illuminate\Http\Resources\Attributes`:

| Attribute | Replaces | Repo status |
|---|---|---|
| `#[Collects(PostResource::class)]` | `public $collects` | ✅ BookingCollection, BookingSessionCollection |
| `#[PreserveKeys]` (marker) | `$preserveKeys = true` | ❌ never used |

`#[UseResource]` / `#[UseResourceCollection]` (Eloquent, see §1) are only ever read by `$model->toResource()` / `$model->toResourceCollection()`. This repo's controllers call `XResource::make($model)` / `XResource::collection($collection)` explicitly — those attributes would be dead code. Adopt them only in the same PR that migrates controllers to `toResource()`.

## 7. Factory attributes

| Attribute | Replaces | Repo status |
|---|---|---|
| `#[UseModel(X::class)]` from `Illuminate\Database\Eloquent\Factories\Attributes\UseModel` | `protected $model` | ✅ 7 factories (6 converted + `CenterMerchandiseFactory` created) |

Model-side `#[UseFactory(XFactory::class)]` (Eloquent namespace) replaces `newFactory()` — only needed when the factory name is not derivable. No model here needs it.

## 8. Testing attributes

Import from `Illuminate\Foundation\Testing\Attributes`: `Seed`, `Seeder`, `SetUp`, `TearDown`, `UnitTest`. Real but not used in this repo (tests use `use RefreshDatabase;` trait — there is NO RefreshDatabase attribute — and standard `setUp()`, which stays).

```php
use Illuminate\Foundation\Testing\Attributes\SetUp;

class OrderTest extends TestCase
{
    private User $user;

    #[SetUp]
    public function createUser(): void
    {
        $this->user = User::factory()->create();
    }
}
```

## 9. Container / DI attributes

Import from `Illuminate\Container\Attributes\`. These attach to constructor/service parameters:

| Attribute | Injects |
|---|---|
| `#[CurrentUser]` / `#[Authenticated('web')]` | authenticated user (nullable aware) |
| `#[Auth('api')]` | auth guard |
| `#[Config('services.stripe.secret')]` | config value |
| `#[Cache('redis')]` | cache store |
| `#[DB('analytics')]` / `#[Database('legacy_mysql')]` | DB connection (alias) |
| `#[Log('payments')]` | log channel |
| `#[Storage('s3')]` | filesystem disk |
| `#[Tag('notification.channels')]` | tagged services |
| `#[RouteParameter('invoice')]` | route parameter (contextual) |
| `#[RequestAttribute('x')]` | request attribute (contextual) |
| `#[Context('request_id')]` | context repository value |
| `#[Give(ConcreteImpl::class)]` | specific concrete implementation |

Class-level registration attributes: `#[Bind]` (new instance), `#[Singleton]`, `#[Scoped]`, `#[BindWhen(...)]` — replace explicit `bind()/singleton()` calls in providers.

```php
use Illuminate\Container\Attributes\Config;

class PaymentService
{
    public function __construct(
        #[Config('services.stripe.secret')] private string $stripeSecret,
    ) {}
}
```

N/A in this repo: services/actions resolve concrete services via constructor DI already; nothing here wants primitive-parameter injection. Observers are resolved through the container regardless of registration path — constructor DI works there too.

---

## Rules for this repo

1. **Attribute-first, everywhere it exists.** New model/job/command/listener/factory/policy/collection code MUST use attributes — no `$fillable`, `$table`, `$signature`, `$tries`, `$model`, `$collects`, etc. properties.
2. **Verify before use.** Any attribute not in the §Canonical inventory is treated as nonexistent. Check `ls vendor/.../Attributes/` and the attribute's constructor before trusting a blog or tutorial.
3. **Spatie translatable:** `#[Translatable(['name'])]` from `Spatie\Translatable\Attributes\Translatable` (with `HasTranslations` trait), not `public array $translatable`.
4. **Formatting:** one attribute per line directly above the class declaration (or the method for method-targeted attrs); imports alphabetical; run `vendor/bin/pint` on touched files.
5. **Observers:** `#[ObservedBy([XObserver::class])]` on the model, observer class `final readonly` with constructor DI. Never ALSO register in `EventServiceProvider::$observers` — double-fires every hook.
6. **Events:** ordinary application event→listener mapping STAYS in `EventServiceProvider::$listen`. No attribute replaces it (`#[ListensTo]` does not exist). Listener queue tuning may use `Illuminate\Queue\Attributes\*`.
7. **Casts, channels, mail, broadcast:** stay methods — `casts()`, `via()`, `envelope()`, `broadcastOn()`, `broadcastAs()`. No attribute exists in any released Laravel.
8. **Policies:** `#[UsePolicy(XPolicy::class)]` on the model. NEVER also list it in a provider `$policies` array — duplicate registration is dead config (Gate resolves the attribute via `getPolicyFor()`).
9. **Collections:** `#[Collects(XResource::class)]` replaces `$collects`. Do NOT add `#[UseResource]`/`#[UseResourceCollection]` to models unless controllers switch to `$model->toResource()` in the same PR.
10. **Controllers/middleware:** middleware stays route-level (`routes/*.php`). No `$this->middleware()` → no `#[Middleware]`.
11. **Post:graphify:** after code changes run `graphify update .`.

## Verification (run after any attribute work)

```bash
# No legacy property/accessor/scope/config left anywhere
grep -rn 'protected \$fillable\|protected \$hidden\|protected \$guarded\|protected \$appends\|protected \$visible\|protected \$with\|protected \$touches\|function scope\|get[A-Z]\w*Attribute(\|protected \$signature\|protected \$model\|public \$collects\|protected \$casts' app database/factories

# Every observer attached exactly once
grep -rn 'ObservedBy' app/Models
grep -n 'observers' app/Providers/EventServiceProvider.php        # expect: no match

# Policy resolves through the attribute
php artisan tinker --execute="app('Illuminate\Contracts\Auth\Access\Gate')->getPolicyFor(App\Models\BookingSession::class)"

# Factories resolve their model
php artisan tinker --execute="App\Models\X::factory()->make()"

# Commands discoverable
php artisan list

# App event listeners intact
php artisan event:list

# Format + full suite
vendor/bin/pint
php artisan test
```

Note: `phpstan`/`larastan` not installed (`vendor/bin/phpstan` absent). Add `larastan/larastan` in `require-dev` if static analysis is wanted.