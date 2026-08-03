<?php
// filePath: routes/web/scheduler/walkin.php

declare(strict_types=1);
use App\Http\Actions\Web\Admin\Scheduler\ProcessExistingWalkInAction;
use App\Http\Actions\Web\Admin\Scheduler\ProcessNewWalkInAction;
use App\Http\Actions\Web\Admin\Scheduler\ValidateWalkInFieldAction;
use Illuminate\Support\Facades\Route;

Route::get('/walkin/validate', ValidateWalkInFieldAction::class)->name('walkin.validate');
Route::prefix('sessions/{sessionId}/walkin')->name('walkin.')->group(function (): void {
    Route::post('/existing', ProcessExistingWalkInAction::class)->name('existing');
    Route::post('/new', ProcessNewWalkInAction::class)->name('new');
});
