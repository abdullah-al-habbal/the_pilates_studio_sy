<?php
declare(strict_types=1);
use App\Http\Actions\Web\Admin\Operations\SendPushNotificationAction;
use Illuminate\Support\Facades\Route;
Route::prefix('notifications')->name('notifications.')->group(function (): void {
    Route::post('/send', SendPushNotificationAction::class)->name('send');
});
