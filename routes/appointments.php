<?php

use App\Http\Controllers\MedAppointmentsController;

/**
 * Medical appointments routes
 */

Route::prefix('appointment')->group(function () {
    Route::get('/fetch', [MedAppointmentsController::class, 'index']);
    Route::post('/single/get', [MedAppointmentsController::class, 'show']);
    Route::post('/get/bydoctor', [MedAppointmentsController::class, 'getByDoctor']);

    Route::post('/create', [MedAppointmentsController::class, 'store']);

    Route::put('/edit', [MedAppointmentsController::class, 'update']);

    Route::post('/cancel', [MedAppointmentsController::class, 'cancel']);
    Route::delete('/delete', [MedAppointmentsController::class, 'destroy']);
});