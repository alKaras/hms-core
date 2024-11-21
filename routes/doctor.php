<?php

use App\Http\Controllers\DoctorController;


/**
 * Doctors routes api/doctors
 */
Route::prefix('doctors')->group(function () {
    // Route::get('/fetch', [DoctorController::class, 'index']);
    Route::get('/fetch/{id}', [DoctorController::class, 'show'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::post('/create', [DoctorController::class, 'store'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::post('/getbyservice', [DoctorController::class, 'showByService'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::put('/edit/{id}', [DoctorController::class, 'update'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::post('/import', [DoctorController::class, 'importDoctors'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::delete('/delete/{id}', [DoctorController::class, 'destroy'])->middleware(['jwt.auth', 'role:admin,manager']);
});