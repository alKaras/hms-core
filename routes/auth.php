<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Profile\PasswordController;
use App\Http\Controllers\Api\Profile\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/email-verify', [AuthController::class, 'verifyUserEmail']);
    Route::post('/email-resend-verification', [AuthController::class, 'resendEmailVerification']);
});

Route::middleware(['jwt.auth'])->group(function () {
    Route::post('/user/change-password', [PasswordController::class, 'changeUserPassword']);

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user/me', [UserController::class, 'getMe']);
});
