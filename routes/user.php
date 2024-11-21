<?php

use App\Http\Controllers\UserReferralController;
use App\Http\Controllers\Api\Profile\UserController;


/**
 * User routes api/user
 */
Route::prefix('user')->group(function () {
    Route::get('fetch', [UserController::class, 'fetchAll'])->middleware(['jwt.auth', 'role:admin,manager,doctor']);

    Route::post('create', [UserController::class, 'store'])->middleware(['jwt.auth', 'role:admin,manager']);

    Route::get('fetch/{id}', [UserController::class, 'show'])->middleware(['jwt.auth', 'role:admin,manager']);

    Route::put('edit/{id}', [UserController::class, 'update'])->middleware(['jwt.auth', 'role:user,admin']);

    Route::post('attach-role/{id}', [UserController::class, 'attachRole'])->middleware(['jwt.auth', 'role:admin']);
    Route::post('detach-role/{id}', [UserController::class, 'detachRole'])->middleware(['jwt.auth', 'role:admin']);
    // Route::delete('delete/{id}', [UserController::class, 'destroy']);
});

/**
 * UserReferral routes api/userreferral
 */
Route::prefix('/userreferral')->group(function () {
    Route::get('/fetch/{id}', [UserReferralController::class, 'show'])->middleware(['jwt.auth', 'role:user']);
    Route::post('/create', [UserReferralController::class, 'store'])->middleware(['jwt.auth', 'role:doctor']);
    // Route::delete('/delete/{id}', [UserReferralController::class, 'destroy']);
    Route::get('/fetch', [UserReferralController::class, 'index'])->middleware(['jwt.auth', 'role:user']);
});