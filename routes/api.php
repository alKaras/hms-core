<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HospitalController;
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