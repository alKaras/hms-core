<?php

use App\Http\Controllers\TimeSlotsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verification', [\App\Http\Controllers\Api\Auth\AuthController::class, 'showVerificationPage']);
Route::get('/api/timeslot/{id}/download-timeslot', [TimeSlotsController::class, 'generatePdf']);