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


Route::post('/webhook', [OrderController::class, 'stripeHookHandler']);
Route::post('/checkout/cancel', [OrderController::class, 'cancel']);
Route::post('/order/get', [FeedController::class, 'getOrderByFilter']);
Route::post('/report/get', [FeedController::class, 'getReportByFilter']);
Route::post('/order/sendconfirmation', [OrderController::class, 'sendOrderConfirmationMail']);

/**
 * Roles routes api/roles
 */
Route::prefix('roles')->group(function () {
    Route::get('/search', [RoleController::class, 'search']);
});
