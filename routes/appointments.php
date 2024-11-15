<?php

use App\Http\Controllers\MedAppointmentsController;

/**
 * Medical appointments routes
 */

Route::prefix('appointment')->group(function () {
    Route::get('/fetch', [MedAppointmentsController::class, 'index']);
    Route::post('/single/get', [MedAppointmentsController::class, 'show']);
    Route::post('/bydoctor/get', [MedAppointmentsController::class, 'getByDoctor']);

    Route::post('/create', [MedAppointmentsController::class, 'store']);

    Route::post('/edit', [MedAppointmentsController::class, 'update']);

    Route::post('/cancel', [MedAppointmentsController::class, 'cancel']);
    Route::delete('/delete', [MedAppointmentsController::class, 'destroy']);

    Route::post('/confirm', [MedAppointmentsController::class, 'confirmAppointment']);

    Route::get('/{id}/download', [MedAppointmentsController::class, 'generateSummaryPdf']);

    Route::post('/summary/send', [MedAppointmentsController::class, 'sendSummaryNotification']);

    Route::post('/byuser/get', [MedAppointmentsController::class, 'getUserAppointments']);
});