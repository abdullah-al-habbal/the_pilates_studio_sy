<?php

// filePath: app/Providers/AppServiceProvider.php

namespace App\Providers;

use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\StaticPage;
use App\Observers\StaticPageObserver;
use App\Policies\BookingSessionPolicy;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\BookingSession\BookingSessionEloquentRepository;
use App\Repositories\Eloquent\ClassCategory\ClassCategoryEloquentRepository;
use App\Repositories\Eloquent\Classes\ClassesEloquentRepository;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Repositories\Eloquent\Instructor\InstructorEloquentRepository;
use App\Repositories\Eloquent\User\UserEloquentRepository;
use App\Services\Booking\BookingService;
use App\Services\BookingSession\BookingSessionService;
use App\Services\ClassCategory\ClassCategoryService;
use App\Services\ClassSession\ClassSessionService;
use App\Services\Dashboard\StatsService;
use App\Services\Instructor\InstructorService;
use App\Services\User\UserService;
use App\Services\Currency\CurrencyService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;

class ApplicationServiceProvider extends ServiceProvider
{
    protected array $policies = [
        BookingSession::class => BookingSessionPolicy::class,
    ];

    public function register(): void
    {
        $this->app->bind(ClassesEloquentRepository::class, function ($app) {
            return new ClassesEloquentRepository($app->make(Classes::class));
        });
        $this->app->bind(ClassSessionEloquentRepository::class, function ($app) {
            return new ClassSessionEloquentRepository($app->make(ClassSession::class));
        });

        $this->app->bind(ClassCategoryEloquentRepository::class);
        $this->app->bind(InstructorEloquentRepository::class);
        $this->app->bind(UserEloquentRepository::class);
        $this->app->bind(BookingEloquentRepository::class);
        $this->app->bind(BookingSessionEloquentRepository::class);

        $this->app->bind(BookingService::class);
        $this->app->bind(BookingSessionService::class);
        $this->app->bind(ClassCategoryService::class);
        $this->app->bind(ClassSessionService::class);
        $this->app->bind(InstructorService::class);
        $this->app->bind(UserService::class);
        $this->app->bind(StatsService::class);
    }

    public function boot(): void
    {
        $this->registerPolicies();
        StaticPage::observe(StaticPageObserver::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        $this->configureModelProtections();
        $this->configureDatabase();
        $this->configurePasswordDefaults();
        $this->configureDashboardCache();
        $this->shareViewData();
    }

    protected function shareViewData(): void
    {
        View::composer('layouts.operations', function ($view) {
            $currencyService = app(CurrencyService::class);
            $view->with([
                'currencyService' => $currencyService,
                'defaultCurrency' => $currencyService->getDefaultCurrency(),
            ]);
        });
    }

    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    protected function configureModelProtections(): void
    {
        $isLocal = $this->app->environment('local', 'development');

        Model::automaticallyEagerLoadRelationships();
        Model::preventSilentlyDiscardingAttributes($isLocal);
        Model::preventAccessingMissingAttributes($isLocal);
        Model::shouldBeStrict($isLocal);
        Model::unguard(false);
    }

    protected function configureDatabase(): void
    {

        // DB::prohibitDestructiveCommands($this->app->environment('production'));

        if ($this->app->environment('production')) {
            if (config('app.force_https', false)) {
                URL::forceScheme('https');
            }

            DB::statement('SET SESSION sql_mode = STRICT_ALL_TABLES');
            DB::statement('SET SESSION group_concat_max_len = 1000000');
        } else {
            DB::listen(function ($query) {
                if ($query->time > 500) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }
    }

    protected function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
    }

    protected function configureDashboardCache(): void
    {
        $models = [Booking::class, BookingSession::class, ClassSession::class];
        foreach ($models as $model) {
            $model::saved(function () {
                $this->invalidateDashboardCache();
            });
            $model::deleted(function () {
                $this->invalidateDashboardCache();
            });
        }
    }

    protected function invalidateDashboardCache(): void
    {
        Cache::forget('dashboard.overview.stats');
        Cache::forget('dashboard.attendance_trend.30');
    }
}
