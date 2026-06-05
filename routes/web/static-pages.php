<?php

declare(strict_types=1);

use App\Actions\Web\StaticPage\ShowStaticPageAction;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/web/static-pages/{slug}', ShowStaticPageAction::class)->name('static-pages.show');
});