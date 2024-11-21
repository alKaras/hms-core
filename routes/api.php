<?php

use App\Http\Controllers\HospitalReviewController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\FeedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\HServicesController;
use App\Http\Controllers\TimeSlotsController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\UserReferralController;
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
require __DIR__ . '/hospital.php';
require __DIR__ . '/user.php';
require __DIR__ . '/service.php';
require __DIR__ . '/doctor.php';
require __DIR__ . '/department.php';
require __DIR__ . '/timeslot.php';
require __DIR__ . '/appointments.php';
require __DIR__ . '/medcard.php';


Route::post('/webhook', [OrderController::class, 'stripeHookHandler']);
Route::post('/checkout/cancel', [OrderController::class, 'cancel'])->middleware(['jwt.auth', 'role:admin,manager,user']);

Route::post('/order/feed/get', [FeedController::class, 'getOrderByFilter'])->middleware(['jwt.auth']);


Route::middleware(['jwt.auth', 'role:admin,manager,doctor'])->group(function () {
    Route::post('/order/sendconfirmation', [OrderController::class, 'sendOrderConfirmationMail']);
    Route::post('/report/get', [FeedController::class, 'getReportByFilter']);
    Route::post('/report/render', [FeedController::class, 'downloadReport']);
});


/**
 * Roles routes api/roles
 */
Route::prefix('roles')->middleware(['jwt.auth', 'role:admin'])->group(function () {
    Route::get('/search', [RoleController::class, 'search']);
});

