<?php

use App\Http\Controllers\HServicesController;

/**
 * Services routes api/services
 */

Route::prefix('services')->group(function () {
    Route::post('/create', [HServicesController::class, 'store']);
    Route::post('/import', [HServicesController::class, 'import']);
    Route::get('/fetch', [HServicesController::class, 'index']);
    Route::get('/fetch/{id}', [HServicesController::class, 'show']);
    Route::post('/getbydoctor', [HServicesController::class, 'getServicesByDoctorId']);
    Route::delete('/delete/{id}', [HServicesController::class, 'destroy']);

    Route::post('/doctors/attach', [HServicesController::class, 'attachDoctors']);
});