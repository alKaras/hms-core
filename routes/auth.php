<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserReferralController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Profile\UserController;
use App\Http\Controllers\Api\Profile\PasswordController;

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
    Route::get('/userreferral/fetch', [UserReferralController::class, 'index']);
});

Route::middleware(['jwt.auth', 'cart.expiration'])->group(function () {
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::get('/cart/get', [CartController::class, 'getCart']);
    Route::delete('/cart/item/{itemId}/remove', [CartController::class, 'removeItem']);
    Route::post('/cart/checkout', [OrderController::class, 'checkout']);
});
