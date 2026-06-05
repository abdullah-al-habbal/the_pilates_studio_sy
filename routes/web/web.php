<?php
// /home/lenovo/work/projects/pilates/routes/web/web.php
declare(strict_types=1);

use App\Actions\Web\Landing\GetLandingDataAction;
use Illuminate\Support\Facades\Route;

Route::get('/', [GetLandingDataAction::class, 'execute'])->name('landing');

require __DIR__ . '/scheduler/index.php';
require __DIR__ . '/operations/index.php';
require __DIR__ . '/locale.php';
require __DIR__ . '/static-pages.php';
require __DIR__ . '/debug.php';