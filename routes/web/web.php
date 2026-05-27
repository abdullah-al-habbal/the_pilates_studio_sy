<?php
// routes\web\web.php
declare(strict_types=1);

use App\Actions\Debug\SendFcmTestNotificationAction;
use App\Actions\V1\Locale\SwitchLocaleAction;
use App\Actions\Web\StaticPage\ShowStaticPageAction;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;


Route::get('/', [LandingController::class, 'index'])->name('landing');

require __DIR__ . '/scheduler.php';
require __DIR__ . '/operations.php';

Route::middleware(['web'])->group(function () {
    Route::get('/web/static-pages/{slug}', ShowStaticPageAction::class)
        ->name('static-pages.show');

    Route::get('/locale/{code}', SwitchLocaleAction::class)
        ->name('locale.switch');
});

Route::get('/debug/fcm', SendFcmTestNotificationAction::class)
    ->name('debug.fcm');