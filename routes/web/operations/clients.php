<?php
declare(strict_types=1);
use App\Http\Actions\Web\Admin\Operations\GetClientsAction;
use App\Http\Actions\Web\Admin\Client\ClientDetailsAction;
use Illuminate\Support\Facades\Route;
Route::prefix('clients')->name('clients.')->group(function (): void {
    Route::get('/', GetClientsAction::class)->name('index');
    Route::get('/{userId}/details', ClientDetailsAction::class)->name('details');
});
