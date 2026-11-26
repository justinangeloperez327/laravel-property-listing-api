<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\PropertyImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
    });

    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::get('properties/{property}', [PropertyController::class, 'show'])->name('properties.show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('me/properties', [PropertyController::class, 'mine'])->name('me.properties');
        Route::get('me/favorites', [FavoriteController::class, 'index'])->name('me.favorites');

        Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::post('properties/{property}/favorite', [FavoriteController::class, 'store'])->name('properties.favorite');
        Route::delete('properties/{property}/favorite', [FavoriteController::class, 'destroy'])->name('properties.unfavorite');
    });

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::post('properties', [PropertyController::class, 'store'])->name('properties.store');
        Route::match(['put', 'patch'], 'properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
        Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');

        Route::post('properties/{property}/images', [PropertyImageController::class, 'store'])->name('properties.images.store');
        Route::delete('properties/{property}/images/{image}', [PropertyImageController::class, 'destroy'])
            ->scopeBindings()
            ->name('properties.images.destroy');
    });
});
