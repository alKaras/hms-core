<?php

use App\Http\Controllers\OrderController;
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

Route::post('/webhook', [OrderController::class, 'stripeHookHandler']);
Route::post('/checkout/cancel', [OrderController::class, 'cancel']);
Route::post('/order/get', [OrderController::class, 'getOrderByFilter']);
Route::post('/order/sendconfirmation', [OrderController::class, 'sendOrderConfirmationMail']);
/**
 * Roles routes api/roles
 */
Route::prefix('roles')->group(function () {
    Route::get('/search', [RoleController::class, 'search']);
});

/**
 * Hospital routes api/hospital
 */
Route::prefix('hospital')->group(function () {
    Route::get('fetch', [HospitalController::class, 'index']);
    Route::get('fetch/{id}', [HospitalController::class, 'show']);
    Route::get('/fetch/{id}/departments', [HospitalController::class, 'showHospitalDepartments']);

    Route::post('/fetch/doctors', [HospitalController::class, 'fetchDepartmentDoctors']);

    Route::post('/fetch/services', [HospitalController::class, 'showHospitalServices']);

    Route::post('create', [HospitalController::class, 'store']);
    Route::put('edit/{id}', [HospitalController::class, 'update']);
    Route::delete('delete/{id}', [HospitalController::class, 'destroy']);
});

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
 * Services routes api/services
 */

Route::prefix('services')->group(function () {
    Route::post('/create', [HServicesController::class, 'store']);
    Route::post('/import', [HServicesController::class, 'import']);
    Route::get('/fetch', [HServicesController::class, 'index']);
    Route::get('/fetch/{id}', [HServicesController::class, 'show']);
    Route::post('/getbydoctor', [HServicesController::class, 'getServicesByDoctorId']);
    Route::delete('/delete/{id}', [HServicesController::class, 'destroy']);
});

/**
 * Doctors routes api/doctors
 */
Route::prefix('doctors')->group(function () {
    Route::get('/fetch', [DoctorController::class, 'index']);
    Route::get('/fetch/{id}', [DoctorController::class, 'show']);
    Route::post('/create', [DoctorController::class, 'store']);
    Route::post('/getbyservice', [DoctorController::class, 'showByServiceId']);
    Route::put('/edit/{id}', [DoctorController::class, 'update']);
    Route::post('/import', [DoctorController::class, 'importDoctors']);
    Route::delete('/delete/{id}', [DoctorController::class, 'destroy']);
});

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

/**
 * UserReferral routes api/userreferral
 */
Route::prefix('/userreferral')->group(function () {
    Route::get('/fetch/{id}', [UserReferralController::class, 'show']);
    Route::post('/create', [UserReferralController::class, 'store']);
    Route::delete('/delete/{id}', [UserReferralController::class, 'destroy']);
});

/**
 * TimeSlots routes api/timeslots
 */
Route::prefix('/timeslots')->group(function () {
    Route::get('/fetch', [TimeSlotsController::class, 'index']);
    Route::get('/{id}/getbyid', [TimeSlotsController::class, 'show']);
    Route::post('/getbydoctor', [TimeSlotsController::class, 'showByDoctor']);
    Route::post('/getbyservice', [TimeSlotsController::class, 'showByService']);
    Route::post('/getbydate', [TimeSlotsController::class, 'showByDate']);
    Route::post('/generate', [TimeSlotsController::class, 'generateTimeSlots']);
    Route::post('/create', [TimeSlotsController::class, 'store']);
    Route::put('/{id}/edit', [TimeSlotsController::class, 'update']);
    Route::delete('/{id}/destroy', [TimeSlotsController::class, 'destroy']);
});