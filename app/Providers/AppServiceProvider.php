<?php
// filePath: app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureModelProtections();
        $this->configureDatabase();
        $this->configurePasswordDefaults();
        $this->configureObservers();
    }

    protected function configureModelProtections(): void
    {
        $isLocal = $this->app->environment('local', 'development');

        Model::preventLazyLoading($isLocal);
        Model::preventSilentlyDiscardingAttributes($isLocal);
        Model::preventAccessingMissingAttributes($isLocal);
        Model::shouldBeStrict($isLocal);
        Model::unguard(false);
    }

    protected function configureDatabase(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());

        if ($this->app->environment('production')) {
            if (config('app.force_https', false)) {
                URL::forceScheme('https');
            }

            DB::statement('SET SESSION sql_mode = STRICT_ALL_TABLES');
            DB::statement('SET SESSION group_concat_max_len = 1000000');
        } else {
            DB::listen(function ($query) {
                if ($query->time > 500) { // Log queries slower than 500ms
                    logger()->warning('Slow query detected', [
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

    protected function configureObservers(): void
    {
    }
}
