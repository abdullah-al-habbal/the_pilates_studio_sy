<?php

declare(strict_types=1);

namespace App\Providers;

use App\Bootstrap\EnvironmentValidator;
use Illuminate\Support\ServiceProvider;

final class BootstrapEnvironmentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        EnvironmentValidator::validate();
    }
}
