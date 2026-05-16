<?php
declare(strict_types=1);

use App\Http\Actions\Web\Admin\HealthCheckAction;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckAction::class)
    ->middleware(['throttle:60,1'])
    ->name('health');
