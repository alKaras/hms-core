<?php

use App\Http\Controllers\TimeSlotsController;

/**
 * TimeSlots routes api/timeslots
 */
Route::prefix('/timeslots')->group(function () {
    // Route::get('/fetch', [TimeSlotsController::class, 'index']);
    // Route::get('/{id}/getbyid', [TimeSlotsController::class, 'show']);
    // Route::post('/getbydoctor', [TimeSlotsController::class, 'showByDoctor']);
    // Route::post('/getbyservice', [TimeSlotsController::class, 'showByService']);

    Route::post('/getbydate', [TimeSlotsController::class, 'showByDate']);
    Route::post('/free/get', [TimeSlotsController::class, 'showFreeSlots']);

    Route::post('/generate', [TimeSlotsController::class, 'generateTimeSlots'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::post('/create', [TimeSlotsController::class, 'store'])->middleware(['jwt.auth', 'role:admin,manager']);

    // Route::put('/{id}/edit', [TimeSlotsController::class, 'update'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::delete('/{id}/destroy', [TimeSlotsController::class, 'destroy'])->middleware(['jwt.auth', 'role:admin,manager']);

    Route::get('/{id}/download-timeslot', [TimeSlotsController::class, 'generatePdf'])->middleware(['jwt.auth', 'role:user,admin,manager']);
});