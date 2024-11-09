<?php

use App\Http\Controllers\UserReferralController;
use App\Http\Controllers\Api\Profile\UserController;


/**
 * User routes api/user
 */
Route::prefix('user')->group(function () {
    Route::get('fetch', [UserController::class, 'fetchAll']);
    Route::post('create', [UserController::class, 'store']);
    Route::get('fetch/{id}', [UserController::class, 'show']);
    Route::put('edit/{id}', [UserController::class, 'update']);
    Route::post('attach-role/{id}', [UserController::class, 'attachRole']);
    Route::post('detach-role/{id}', [UserController::class, 'detachRole']);
    Route::delete('delete/{id}', [UserController::class, 'destroy']);
});

/**
 * UserReferral routes api/userreferral
 */
Route::prefix('/userreferral')->group(function () {
    Route::get('/fetch/{id}', [UserReferralController::class, 'show']);
    Route::post('/create', [UserReferralController::class, 'store']);
    Route::delete('/delete/{id}', [UserReferralController::class, 'destroy']);
});