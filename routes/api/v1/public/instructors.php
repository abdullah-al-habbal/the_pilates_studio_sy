<?php

// filePath: routes/api/v1/public/instructors.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\Instructor\InstructorController;
use Illuminate\Support\Facades\Route;

Route::get('instructors/{id}', [InstructorController::class, 'show'])->name('instructors.show');
