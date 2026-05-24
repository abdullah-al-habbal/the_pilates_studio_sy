<?php
// routes\web\web.php
declare(strict_types=1);

use App\Actions\V1\StaticPage\GetStaticPageBySlug\GetStaticPageBySlugAction;
use App\Actions\V1\Locale\SwitchLocaleAction;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/scheduler.php';
require __DIR__ . '/operations.php';

Route::middleware(['web'])->group(function () {
    Route::get('/web/static-pages/{slug}', GetStaticPageBySlugAction::class)
        ->name('static-pages.show');

    Route::get('/locale/{code}', SwitchLocaleAction::class)
        ->name('locale.switch');
});

Route::get('/debug/fcm', function () {
    $user = App\Models\User::with('settings')->first();

    $user->notify(new App\Notifications\ManualPushNotification(
        'Test',
        'Direct HTTP check'
    ));

    return 'sent';
});