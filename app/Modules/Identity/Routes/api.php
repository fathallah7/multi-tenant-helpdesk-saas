<?php

use App\Modules\Identity\Http\Controllers\LoginController;
use App\Modules\Identity\Http\Controllers\LogoutController;
use App\Modules\Identity\Http\Controllers\MeController;
use App\Modules\Identity\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [
        RegisterController::class,
        'store',
    ])->middleware('throttle:5,1');

    Route::post('/login', [
        LoginController::class,
        'store',
    ])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [
            MeController::class,
            'show',
        ]);

        Route::post('/logout', [
            LogoutController::class,
            'destroy',
        ]);
    });
});
