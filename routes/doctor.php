<?php

use App\Http\Controllers\DoctorController;


/**
 * Doctors routes api/doctors
 */
Route::prefix('doctors')->group(function () {
    Route::get('/fetch', [DoctorController::class, 'index']);
    Route::get('/fetch/{id}', [DoctorController::class, 'show']);
    Route::post('/create', [DoctorController::class, 'store']);
    Route::post('/getbyservice', [DoctorController::class, 'showByService']);
    Route::put('/edit/{id}', [DoctorController::class, 'update']);
    Route::post('/import', [DoctorController::class, 'importDoctors']);
    Route::delete('/delete/{id}', [DoctorController::class, 'destroy']);
});