<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\PropertyImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
    });

    Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::get('properties/{property}', [PropertyController::class, 'show'])->name('properties.show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('me/properties', [PropertyController::class, 'mine'])->name('me.properties');
        Route::get('me/favorites', [FavoriteController::class, 'index'])->name('me.favorites');

        Route::post('properties', [PropertyController::class, 'store'])->name('properties.store');
        Route::match(['put', 'patch'], 'properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
        Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');

        Route::post('properties/{property}/images', [PropertyImageController::class, 'store'])->name('properties.images.store');
        Route::delete('properties/{property}/images/{image}', [PropertyImageController::class, 'destroy'])
            ->scopeBindings()
            ->name('properties.images.destroy');

        Route::post('properties/{property}/favorite', [FavoriteController::class, 'store'])->name('properties.favorite');
        Route::delete('properties/{property}/favorite', [FavoriteController::class, 'destroy'])->name('properties.unfavorite');
    });
});
