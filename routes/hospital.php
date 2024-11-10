<?php

use App\Http\Controllers\HospitalController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HospitalReviewController;


/**
 * Hospital routes api/hospital
 */
Route::prefix('hospital')->group(function () {
    Route::get('fetch', [HospitalController::class, 'index']);
    Route::get('fetch/{id}', [HospitalController::class, 'show']);
    Route::get('/fetch/{id}/departments', [HospitalController::class, 'showHospitalDepartments']);

    Route::post('/fetch/doctors', [HospitalController::class, 'fetchDepartmentDoctors']);

    Route::post('/fetch/services', [HospitalController::class, 'showHospitalServices']);

    Route::post('/rating', [HospitalController::class, 'getAverageRatingForSpecificHospital']);

    Route::post('create', [HospitalController::class, 'store']);
    Route::put('edit/{id}', [HospitalController::class, 'update']);
    Route::delete('delete/{id}', [HospitalController::class, 'destroy']);

    Route::post('/department/attach', [HospitalController::class, 'attachExistedDepartments']);
    Route::post('/department/list/unassigned', [DepartmentController::class, 'getUnassignedDepartments']);
});

/**
 * Hospital Reviews routes api/hospital/reviews
 */
Route::prefix('/hospital/reviews')->group(function () {
    Route::post('/get', [HospitalReviewController::class, 'index']);
    Route::post('/getcount', [HospitalReviewController::class, 'getAmountOfReviewsByHospital']);
});

Route::prefix('/hospital/reviews')->middleware(['jwt.auth'])->group(function () {
    Route::post('/create', [HospitalReviewController::class, 'store']);
    Route::delete('/item/{id}/remove', [HospitalReviewController::class, 'destroy']);
});