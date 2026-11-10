<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth-register')
        ->name('auth.register');
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail'])
        ->middleware('throttle:auth-verify-email')
        ->name('auth.verify-email');
    Route::post('/auth/resend-otp', [AuthController::class, 'resendOtp'])
        ->middleware('throttle:auth-resend-otp')
        ->name('auth.resend-otp');
    Route::post('/auth/google', [AuthController::class, 'google'])
        ->middleware('throttle:auth-google')
        ->name('auth.google');
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth-login')
        ->name('auth.login');
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:auth-refresh')
        ->name('auth.refresh');

    Route::middleware('auth.token')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::middleware('account.active')->group(function (): void {
            Route::get('/auth/me', [AuthController::class, 'me'])
                ->middleware('permission:profile.view')
                ->name('auth.me');
            Route::put('/auth/complete-profile', [AuthController::class, 'completeProfile'])
                ->middleware(['email.verified', 'permission:profile.update'])
                ->name('auth.complete-profile');
        });
    });
});
