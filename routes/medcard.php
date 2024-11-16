<?php

use App\Http\Controllers\MedCardController;


Route::prefix('medcard')->group(function () {
    Route::get('/fetch', [MedCardController::class, 'index']);
    Route::get('/{:id}/get', [MedCardController::class, 'showById']);

    Route::post('/user/get', [MedCardController::class, 'showByUser']);
    Route::post('/create', [MedCardController::class, 'store']);

    Route::post('/edit', [MedCardController::class, 'update']);
    Route::delete('/delete', [MedCardController::class, 'destroy']);

});