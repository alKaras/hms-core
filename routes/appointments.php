<?php

use App\Http\Controllers\MedAppointmentsController;

/**
 * Medical appointments routes
 */

Route::prefix('appointment')->middleware(['jwt.auth', 'role:doctor'])->group(function () {

    Route::post('/bydoctor/get', [MedAppointmentsController::class, 'getByDoctor']);
    Route::post('/cancel', [MedAppointmentsController::class, 'cancel']);
    Route::post('/confirm', [MedAppointmentsController::class, 'confirmAppointment']);
    Route::post('/summary/send', [MedAppointmentsController::class, 'sendSummaryNotification']);
});

// Not used endpoints. Can be implemented on frontend in further realizations
// Route::prefix('appointment')->middleware(['jwt.auth', 'role:admin'])->group(function () {
//     Route::get('/fetch', [MedAppointmentsController::class, 'index']);
//     Route::post('/create', [MedAppointmentsController::class, 'store']);
//     Route::post('/edit', [MedAppointmentsController::class, 'update']);
//     Route::delete('/delete', [MedAppointmentsController::class, 'destroy']);
// });


Route::post('/appointment/single/get', [MedAppointmentsController::class, 'show'])
    ->middleware(['jwt.auth', 'role:user,doctor']);

Route::post('/appointment/byuser/get', [MedAppointmentsController::class, 'getUserAppointments'])
    ->middleware(['jwt.auth', 'role:user']);

Route::get('/appointment/{id}/download', [MedAppointmentsController::class, 'generateSummaryPdf'])
    ->middleware(['jwt.auth', 'role:user,doctor']);
