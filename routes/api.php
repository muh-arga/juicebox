<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    
    Route::prefix('users')->group(function() {
        Route::get('', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show'])->whereNumber('user');
    });

    Route::prefix('posts')->group(function() {
        Route::get('', [PostController::class, 'index']);
        Route::post('', [PostController::class, 'store']);
        Route::get('/{post}', [PostController::class, 'show'])->whereNumber('post');
        Route::delete('/{post}', [PostController::class, 'destroy'])->whereNumber('post');
        Route::patch('/{post}', [PostController::class, 'update'])->whereNumber('post');
    });

    Route::get('weather', [WeatherController::class, 'show']);
});