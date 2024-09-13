<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\HServicesController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Profile\UserController;
use App\Http\Controllers\Api\Profile\PasswordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

require __DIR__ . '/auth.php';

Route::prefix('hospital')->group(function () {
    Route::get('fetch', [HospitalController::class, 'index']);
    Route::get('fetch/{id}', [HospitalController::class, 'show']);
    Route::post('create', [HospitalController::class, 'store']);
    Route::put('edit/{id}', [HospitalController::class, 'update']);
    Route::delete('delete/{id}', [HospitalController::class, 'destroy']);
});

Route::prefix('user')->group(function () {
    Route::get('fetch', [UserController::class, 'fetchAll']);
    Route::post('create', [UserController::class, 'store']);
    Route::get('fetch/{id}', [UserController::class, 'show']);
    Route::put('edit/{id}', [UserController::class, 'update']);
    Route::post('attach-role/{id}', [UserController::class, 'attachRole']);
    Route::post('detach-role/{id}', [UserController::class, 'detachRole']);
    Route::delete('delete/{id}', [UserController::class, 'destroy']);
});

Route::prefix('services')->group(function () {
    Route::post('/create', [HServicesController::class, 'store']);
    Route::post('/import', [HServicesController::class, 'import']);
    Route::get('/fetch', [HServicesController::class, 'index']);
    Route::get('/fetch/{id}', [HServicesController::class, 'show']);
    Route::delete('/delete/{id}', [HServicesController::class, 'destroy']);
});

Route::prefix('doctors')->group(function () {
    Route::get('/fetch', [DoctorController::class, 'index']);
    Route::get('/fetch/{id}', [DoctorController::class, 'show']);
    Route::post('/create', [DoctorController::class, 'store']);
    Route::post('/edit/{id}', [DoctorController::class, 'update']);
    Route::post('/import', [DoctorController::class, 'importDoctors']);
    Route::delete('/delete/{id}', [DoctorController::class, 'destroy']);
});

Route::prefix('/department')->group(function () {
    Route::get('/fetch', [DepartmentController::class, 'index']);
    Route::get('/fetch/{id}', [DepartmentController::class, 'show']);
    Route::post('/create', [DepartmentController::class, 'store']);
    Route::post('/edit/{id}', [DepartmentController::class, 'update']);
    Route::post('/import', [DepartmentController::class, 'import']);
    Route::delete('/delete/{id}', [DepartmentController::class, 'destroy']);
});