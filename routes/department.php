<?php

use App\Http\Controllers\DepartmentController;


/**
 * Department routes api/department
 */
Route::prefix('/department')->group(function () {
    Route::get('/fetch', [DepartmentController::class, 'index']);
    Route::get('/fetch/{id}', [DepartmentController::class, 'show']);
    Route::post('/create', [DepartmentController::class, 'store']);
    Route::put('/edit/{id}', [DepartmentController::class, 'update']);
    Route::post('/import', [DepartmentController::class, 'import']);
    Route::delete('/delete/{id}', [DepartmentController::class, 'destroy']);
});