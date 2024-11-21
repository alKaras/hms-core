<?php

use App\Http\Controllers\HServicesController;

/**
 * Services routes api/services
 */

Route::prefix('services')->group(function () {
    Route::post('/create', [HServicesController::class, 'store'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::post('/import', [HServicesController::class, 'import'])->middleware(['jwt.auth', 'role:admin,manager']);
    // Route::get('/fetch', [HServicesController::class, 'index']);
    // Route::get('/fetch/{id}', [HServicesController::class, 'show']);
    Route::post('/getbydoctor', [HServicesController::class, 'getServicesByDoctorId'])->middleware(['jwt.auth', 'role:admin,manager,doctor']);
    Route::delete('/delete/{id}', [HServicesController::class, 'destroy'])->middleware(['jwt.auth', 'role:admin,manager']);

    Route::post('/doctors/attach', [HServicesController::class, 'attachDoctors'])->middleware(['jwt.auth', 'role:admin,manager']);
});