<?php

// filePath: app/Providers/AppServiceProvider.php

namespace App\Providers;

use App\Models\BookingSession;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Package;
use App\Models\Testimonial;
use App\Policies\BookingSessionPolicy;
use App\Repositories\Eloquent\AppSetting\AppSettingEloquentRepository;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\BookingSession\BookingSessionEloquentRepository;
use App\Repositories\Eloquent\ClassCategory\ClassCategoryEloquentRepository;
use App\Repositories\Eloquent\Classes\ClassesEloquentRepository;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Repositories\Eloquent\Instructor\InstructorEloquentRepository;
use App\Repositories\Eloquent\Package\PackageEloquentRepository;
use App\Repositories\Eloquent\StaticPage\StaticPageEloquentRepository;
use App\Repositories\Eloquent\Testimonial\TestimonialEloquentRepository;
use App\Repositories\Eloquent\User\UserEloquentRepository;
use App\Services\AppSetting\AppSettingService;
use App\Services\Booking\BookingService;
use App\Services\BookingSession\BookingSessionService;
use App\Services\ClassCategory\ClassCategoryService;
use App\Services\ClassSession\ClassSessionService;
use App\Services\Classes\ClassesService;
use App\Services\Dashboard\StatsService;
use App\Services\Instructor\InstructorService;
use App\Services\Landing\LandingDataService;
use App\Services\Package\PackageService;
use App\Services\StaticPage\StaticPageService;
use App\Services\Testimonial\TestimonialService;
use App\Services\User\UserService;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\PricingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
        $this->app->bind(ClassesService::class);
        $this->app->bind(InstructorService::class);
        $this->app->bind(UserService::class);
        $this->app->bind(StatsService::class);

        $this->app->bind(PackageEloquentRepository::class, function ($app) {
            return new PackageEloquentRepository(
                $app->make(CurrencyService::class),
                $app->make(PricingService::class),
                $app->make(Package::class)
            );
        });
        $this->app->bind(PackageService::class);

        $this->app->bind(AppSettingEloquentRepository::class);
        $this->app->bind(AppSettingService::class);

        $this->app->bind(StaticPageEloquentRepository::class);
        $this->app->bind(StaticPageService::class);

        $this->app->bind(TestimonialEloquentRepository::class, function ($app) {
            return new TestimonialEloquentRepository($app->make(Testimonial::class));
        });
        $this->app->bind(TestimonialService::class);

        $this->app->bind(LandingDataService::class);
    }

    public function boot(): void
    {
        $this->registerPolicies();

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        $this->configureModelProtections();
        $this->configureDatabase();
        $this->configurePasswordDefaults();
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

}
