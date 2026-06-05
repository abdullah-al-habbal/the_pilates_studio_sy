<?php

declare(strict_types=1);

use App\Actions\V1\Locale\SwitchLocaleAction;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/locale/{code}', SwitchLocaleAction::class)->name('locale.switch');
});