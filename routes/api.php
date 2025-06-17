<?php

use Illuminate\Support\Facades\Route;

// Admin Routes
Route::prefix('/admin')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\Admin\AuthController::class, 'register']);

    Route::group(['middleware' => ['auth:sanctum', 'isAdmin']], function () {
        Route::apiResource('/donations', \App\Http\Controllers\Api\Admin\DonationController::class);
        Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'getAllUsers']);
        Route::prefix('/histories')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\DonationController::class, 'getDonationHistories']);
            Route::get('/{userDonation}', [\App\Http\Controllers\Api\Admin\DonationController::class, 'getDonationHistoryDetails']);
        });
        Route::get('/statistics', [\App\Http\Controllers\Api\Admin\DonationController::class, 'getDonationStatistics']);
    });
});
// End Admin Routes

// Auth Routes
Route::prefix('/auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\Public\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\Public\AuthController::class, 'login']);
    Route::post('/login/google', [\App\Http\Controllers\Api\Public\AuthController::class, 'loginGoogle']);

    Route::get('/confirm-email/verify/{id}', [\App\Http\Controllers\Api\Public\AuthController::class, 'verifyConfirmEmail'])->name('verification.verify');
    Route::get('/confirm-email/resend', [\App\Http\Controllers\Api\Public\AuthController::class, 'resendConfirmEmail'])->name('verification.resend');

    Route::post('/reset-password', [\App\Http\Controllers\Api\Public\AuthController::class, 'resetPassword']);
    Route::post('/verify-otp', [\App\Http\Controllers\Api\Public\AuthController::class, 'verifyOtp']);


    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('/logout', [\App\Http\Controllers\Api\Public\AuthController::class, 'logout']);
        Route::post('/set-password', [\App\Http\Controllers\Api\Public\AuthController::class, 'setPassword']);
        Route::post('/change-password', [\App\Http\Controllers\Api\Public\AuthController::class, 'changePassword']);
        Route::get('/user', [\App\Http\Controllers\Api\Public\AuthController::class, 'user']);
    });
});
// End Auth Routes

// Authenticated User Routes
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::patch('/update-profile', [\App\Http\Controllers\Api\Public\UserController::class, 'updateProfile']);
    Route::post('/donate', [\App\Http\Controllers\Api\Public\DonateController::class, 'donate']);
    Route::get('/donate/{userDonation}/notifications', [\App\Http\Controllers\Api\Public\DonateController::class, 'donateNotification']);
});
// End Authenticated User Routes

// Public Routes
Route::prefix('donations')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Public\DonationController::class, 'getAllDonationForUsers']);
    Route::get('/{donation}', [\App\Http\Controllers\Api\Public\DonationController::class, 'getDonationDetailForUsers']);
});
// End Public Routes

// Webhook Routes
Route::prefix('/webhooks')->group(function () {
    Route::post('/payment-notification', [\App\Http\Controllers\Api\Webhook\PaymentController::class, 'paymentNotification']);
});
// End Webhook Routes
