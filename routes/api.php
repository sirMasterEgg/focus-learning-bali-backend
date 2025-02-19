<?php

use Illuminate\Support\Facades\Route;

Route::post('/admin/register', [\App\Http\Controllers\Api\AdminController::class, 'register']);

Route::prefix('/auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/login/google', [\App\Http\Controllers\Api\AuthController::class, 'loginGoogle']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');

    Route::get('/confirm-email/verify/{id}', [\App\Http\Controllers\Api\AuthController::class, 'verifyConfirmEmail'])->name('verification.verify');
    Route::get('/confirm-email/resend', [\App\Http\Controllers\Api\AuthController::class, 'resendConfirmEmail'])->name('verification.resend');

    Route::post('/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword']);
    Route::post('/verify-otp', [\App\Http\Controllers\Api\AuthController::class, 'verifyOtp']);

    Route::post('/change-password', [\App\Http\Controllers\Api\AuthController::class, 'changePassword'])->middleware('auth:sanctum');
    Route::get('/user', [\App\Http\Controllers\Api\AuthController::class, 'user'])->middleware('auth:sanctum');
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::apiResource('/categories', \App\Http\Controllers\Api\CategoryController::class);
    Route::apiResource('/donations', \App\Http\Controllers\Api\DonationController::class);
});


