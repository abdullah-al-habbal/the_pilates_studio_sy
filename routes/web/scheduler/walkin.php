<?php
declare(strict_types=1);
use App\Http\Actions\Web\Admin\Scheduler\{
    ValidateWalkInFieldAction,
    ProcessExistingWalkInAction,
    ProcessNewWalkInAction,
};
use Illuminate\Support\Facades\Route;
// Standalone validation route (no session ID)
Route::get('/walkin/validate', ValidateWalkInFieldAction::class)->name('walkin.validate');
// Session‑specific walk‑in routes
Route::prefix('sessions/{sessionId}/walkin')->name('walkin.')->group(function (): void {
    Route::post('/existing', ProcessExistingWalkInAction::class)->name('existing');
    Route::post('/new', ProcessNewWalkInAction::class)->name('new');
});
