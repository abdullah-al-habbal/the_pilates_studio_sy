<?php
// routes\web\admin.php
declare(strict_types=1);

use App\Http\Actions\Web\Admin\HealthCheckAction;
use Illuminate\Support\Facades\Route;

Route::get('/admin/health', HealthCheckAction::class)
    ->middleware(['web', 'auth', 'throttle:10,1'])
    ->name('admin.health');
