<?php

use App\Http\Controllers\DepartmentController;


/**
 * Department routes api/department
 */
Route::prefix('/department')->group(function () {

    Route::get('/fetch/{id}', [DepartmentController::class, 'show'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::post('/create', [DepartmentController::class, 'store'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::put('/edit/{id}', [DepartmentController::class, 'update'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::post('/import', [DepartmentController::class, 'import'])->middleware(['jwt.auth', 'role:admin,manager']);
    Route::delete('/delete/{id}', [DepartmentController::class, 'destroy'])->middleware(['jwt.auth', 'role:admin,manager']);
});

Route::prefix('/department')->middleware(['jwt.auth', 'role:admin'])->group(function () {
    Route::get('/fetch', [DepartmentController::class, 'index']);
});