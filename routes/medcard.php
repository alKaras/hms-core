<?php

use App\Http\Controllers\MedCardController;


Route::prefix('medcard')->group(function () {
    // Route::get('/fetch', [MedCardController::class, 'index']);
    // Route::get('/{id}/get', [MedCardController::class, 'showById']);

    Route::post('/user/get', [MedCardController::class, 'showByUser'])->middleware(['jwt.auth', 'role:doctor,user']);
    Route::post('/create', [MedCardController::class, 'store'])->middleware(['jwt.auth', 'role:user']);

    Route::post('/edit', [MedCardController::class, 'update'])->middleware(['jwt.auth', 'role:user']);
    // Route::delete('/delete', [MedCardController::class, 'destroy']);

});